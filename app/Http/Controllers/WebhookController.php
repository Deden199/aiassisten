<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Webhook as StripeWebhook;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $sig = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        try {
            $event = StripeWebhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        $type = $event->type ?? '';

        if ($type === 'checkout.session.completed') {
            $session = $event->data->object;
            if (($session->mode ?? null) === 'subscription') {
                $subscriptionId = $session->subscription ?? null;
                $tenantId = $session->metadata->tenant_id ?? null;
                $planId   = $session->metadata->plan_id ?? null;

                if ($subscriptionId && $tenantId) {
                    $sub = $stripe->subscriptions->retrieve($subscriptionId);
                    Subscription::updateOrCreate(
                        ['tenant_id' => $tenantId],
                        [
                            'plan_id'      => $planId,
                            'provider'     => 'stripe',
                            'provider_ref' => $subscriptionId,
                            'status'       => $sub->status ?? 'active',
                            'started_at'   => now(),
                            'renews_at'    => isset($sub->current_period_end) ? now()->createFromTimestamp($sub->current_period_end) : null,
                        ]
                    );
                }
            }
        }

        if ($type === 'invoice.paid' || $type === 'invoice.payment_succeeded') {
            $inv = $event->data->object;
            $subscriptionId = $inv->subscription ?? null;

            // Coba cari tenant melalui subscription record lokal
            $subRow = Subscription::where('provider', 'stripe')
                ->where('provider_ref', $subscriptionId)->first();

            if ($subRow) {
                Invoice::create([
                    'tenant_id'           => $subRow->tenant_id,
                    'provider'            => 'stripe',
                    'provider_invoice_id' => $inv->id ?? (string) Str::ulid(),
                    'amount_due_cents'    => (int) (($inv->amount_due ?? 0)),
                    'amount_paid_cents'   => (int) (($inv->amount_paid ?? 0)),
                    'currency'            => strtoupper($inv->currency ?? 'USD'),
                    'status'              => ($inv->paid ?? false) ? 'paid' : 'open',
                    'paid_at'             => ($inv->paid ?? false) ? now() : null,
                    'hosted_url'          => $inv->hosted_invoice_url ?? null,
                    'pdf_url'             => $inv->invoice_pdf ?? null,
                    'raw'                 => ['stripe' => $inv],
                ]);
            }
        }

        if (in_array($type, ['customer.subscription.updated', 'customer.subscription.deleted'])) {
            $sub = $event->data->object;
            Subscription::where('provider', 'stripe')
                ->where('provider_ref', $sub->id ?? '')
                ->update([
                    'status'    => $sub->status ?? null,
                    'renews_at' => isset($sub->current_period_end) ? now()->createFromTimestamp($sub->current_period_end) : null,
                ]);
        }

        return response()->json(['received' => true]);
    }

    public function paypal(Request $request)
    {
        $headers = [
            'transmission_id'    => $request->header('Paypal-Transmission-Id'),
            'transmission_time'  => $request->header('Paypal-Transmission-Time'),
            'cert_url'           => $request->header('Paypal-Cert-Url'),
            'auth_algo'          => $request->header('Paypal-Auth-Algo'),
            'transmission_sig'   => $request->header('Paypal-Transmission-Sig'),
            'webhook_id'         => config('services.paypal.webhook_id'),
        ];

        $mode = config('services.paypal.mode', 'sandbox');
        $base = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        // Access token
        $tokenResp = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post($base.'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (!$tokenResp->ok()) {
            return response()->json(['error' => 'oauth_failed'], 400);
        }

        $access = $tokenResp->json('access_token');

        // Verify webhook signature
        $verifyResp = Http::withToken($access)->post($base.'/v1/notifications/verify-webhook-signature', [
            'transmission_id'   => $headers['transmission_id'],
            'transmission_time' => $headers['transmission_time'],
            'cert_url'          => $headers['cert_url'],
            'auth_algo'         => $headers['auth_algo'],
            'transmission_sig'  => $headers['transmission_sig'],
            'webhook_id'        => $headers['webhook_id'],
            'webhook_event'     => $request->json()->all(),
        ]);

        if (!$verifyResp->ok() || $verifyResp->json('verification_status') !== 'SUCCESS') {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        $event = $request->json()->all();
        $type  = $event['event_type'] ?? '';
        $res   = $event['resource'] ?? [];

        if ($type === 'BILLING.SUBSCRIPTION.ACTIVATED' || $type === 'BILLING.SUBSCRIPTION.UPDATED') {
            $tenantId = $res['custom_id'] ?? null;
            $providerRef = $res['id'] ?? null;
            $status = strtolower($res['status'] ?? 'active'); // ACTIVE/CANCELLED
            $planId = null;

            if (!empty($res['plan_id'])) {
                $price = Price::where('provider', 'paypal')->where('provider_price_id', $res['plan_id'])->first();
                $planId = $price?->plan_id;
            }

            if ($tenantId && $providerRef) {
                Subscription::updateOrCreate(
                    ['tenant_id' => $tenantId],
                    [
                        'plan_id'      => $planId,
                        'provider'     => 'paypal',
                        'provider_ref' => $providerRef,
                        'status'       => $status === 'active' ? 'active' : 'canceled',
                        'started_at'   => now(),
                        'renews_at'    => null, // PayPal period end tidak selalu dikirim di event ini
                    ]
                );
            }
        }

        if ($type === 'PAYMENT.SALE.COMPLETED' || $type === 'PAYMENT.CAPTURE.COMPLETED') {
            // Ambil subscription ID dari resource chain jika ada
            $invoiceId = $res['id'] ?? (string) Str::ulid();
            $amount = $res['amount']['total'] ?? ($res['amount']['value'] ?? '0.00');
            $currency = $res['amount']['currency'] ?? ($res['amount']['currency_code'] ?? 'USD');

            // Cari subscription melalui parent/links (best effort)
            $subRow = Subscription::where('provider', 'paypal')->latest()->first(); // fallback
            if ($subRow) {
                Invoice::create([
                    'tenant_id'           => $subRow->tenant_id,
                    'provider'            => 'paypal',
                    'provider_invoice_id' => $invoiceId,
                    'amount_due_cents'    => (int) round((float) $amount * 100),
                    'amount_paid_cents'   => (int) round((float) $amount * 100),
                    'currency'            => strtoupper($currency),
                    'status'              => 'paid',
                    'paid_at'             => now(),
                    'hosted_url'          => null,
                    'pdf_url'             => null,
                    'raw'                 => ['paypal' => $res],
                ]);
            }
        }

        if ($type === 'BILLING.SUBSCRIPTION.CANCELLED') {
            $providerRef = $res['id'] ?? null;
            if ($providerRef) {
                Subscription::where('provider', 'paypal')
                    ->where('provider_ref', $providerRef)
                    ->update(['status' => 'canceled']);
            }
        }

        return response()->json(['received' => true]);
    }
}
