<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig,
                config('services.stripe.webhook_secret')
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        switch ($event->type) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $data = $event->data->object;
                $subscription = Subscription::firstOrNew([
                    'tenant_id' => $data->metadata->tenant_id ?? null,
                ]);
                $subscription->provider = 'stripe';
                $subscription->plan_id = $data->metadata->plan_id ?? $subscription->plan_id;
                $subscription->provider_subscription_id = $data->id;
                $subscription->provider_customer_id = $data->customer;
                $subscription->status = $data->status;
                $subscription->cancel_at_period_end = (bool) $data->cancel_at_period_end;
                $subscription->current_period_start = Carbon::createFromTimestamp($data->current_period_start);
                $subscription->current_period_end = Carbon::createFromTimestamp($data->current_period_end);
                $subscription->trial_end_at = $data->trial_end ? Carbon::createFromTimestamp($data->trial_end) : null;
                $subscription->save();
                break;

            case 'invoice.payment_succeeded':
            case 'invoice.payment_failed':
                $inv = $event->data->object;
                $invoice = Invoice::updateOrCreate(
                    ['provider' => 'stripe', 'provider_invoice_id' => $inv->id],
                    [
                        'tenant_id' => $inv->metadata->tenant_id ?? null,
                        'amount_due_cents' => $inv->amount_due,
                        'currency' => $inv->currency,
                        'hosted_url' => $inv->hosted_invoice_url,
                        'pdf_url' => $inv->invoice_pdf,
                        'paid_at' => $event->type === 'invoice.payment_succeeded' ? now() : null,
                        'raw' => $inv,
                    ]
                );
                Subscription::where('provider_subscription_id', $inv->subscription)
                    ->update(['latest_invoice_id' => $invoice->id]);
                break;
        }

        return response()->json(['status' => 'ok']);
    }

    public function paypal(Request $request)
    {
        $event = $request->all();
        $resource = $event['resource'] ?? [];
        $type = $event['event_type'] ?? '';

        if (str_starts_with($type, 'BILLING.SUBSCRIPTION')) {
            $subscription = Subscription::firstOrNew([
                'tenant_id' => $resource['custom_id'] ?? null,
            ]);
            $subscription->provider = 'paypal';
            $subscription->provider_subscription_id = $resource['id'] ?? $subscription->provider_subscription_id;
            $subscription->status = match ($type) {
                'BILLING.SUBSCRIPTION.CANCELLED' => 'canceled',
                'BILLING.SUBSCRIPTION.SUSPENDED' => 'suspended',
                default => 'active',
            };
            $subscription->current_period_start = now();
            $subscription->current_period_end = now();
            $subscription->save();
        }

        if (in_array($type, ['PAYMENT.SALE.COMPLETED', 'PAYMENT.FAILED'])) {
            Invoice::updateOrCreate(
                ['provider' => 'paypal', 'provider_invoice_id' => $resource['id'] ?? ''],
                [
                    'tenant_id' => $resource['custom_id'] ?? null,
                    'amount_due_cents' => (int) (($resource['amount']['value'] ?? 0) * 100),
                    'currency' => $resource['amount']['currency_code'] ?? 'USD',
                    'hosted_url' => $resource['links'][0]['href'] ?? null,
                    'paid_at' => $type === 'PAYMENT.SALE.COMPLETED' ? now() : null,
                    'raw' => $resource,
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }
}
