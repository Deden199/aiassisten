<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Price;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\StripeClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Subscriptions\SubscriptionsCreateRequest;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $subscription = Subscription::with('plan.prices')
            ->where('tenant_id', $tenantId)
            ->latest()
            ->first();

        $plans = Plan::with(['prices' => function ($q) {
$q->where('is_active', true)
  ->orderByRaw("CASE `interval` WHEN 'monthly' THEN 0 WHEN 'yearly' THEN 1 ELSE 2 END");
            }])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $invoices = Invoice::where('tenant_id', $tenantId)->latest()->limit(24)->get();

        return view('billing', compact('subscription', 'plans', 'invoices'));
    }

    public function subscribe(Request $request)
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $data = $request->validate([
            'price_id' => ['required','string', Rule::exists('prices','id')],
        ]);

        $price = Price::with('plan')->where('is_active', true)->findOrFail($data['price_id']);
        $provider = $price->provider ?: 'manual';
        $currency = $price->currency ?: config('billing.currency', 'USD');

        if ($provider === 'stripe') {
            return $this->subscribeStripe($request, $price, $currency);
        }

        if ($provider === 'paypal') {
            return $this->subscribePayPal($request, $price, $currency);
        }

        // Fallback: manual (dev/demo)
        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id'      => $price->plan_id,
                'provider'     => 'manual',
                'provider_ref' => null,
                'status'       => 'active',
                'started_at'   => now(),
                'renews_at'    => $price->interval === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]
        );

        Invoice::create([
            'tenant_id'           => $tenant->id,
            'provider'            => 'manual',
            'provider_invoice_id' => (string) Str::ulid(),
            'amount_due_cents'    => (int) $price->amount_cents,
            'amount_paid_cents'   => (int) $price->amount_cents,
            'currency'            => $currency,
            'status'              => 'paid',
            'paid_at'             => now(),
            'hosted_url'          => null,
            'pdf_url'             => null,
            'raw'                 => ['note' => 'Local/Manual subscription'],
        ]);

        return redirect()->route('billing')->with('success', 'Subscription activated (manual).');
    }

    protected function subscribeStripe(Request $request, Price $price, string $currency)
    {
        $tenant = $request->user()->tenant;
        $success = config('billing.success_url');
        $cancel  = config('billing.cancel_url');

        $stripe = new StripeClient(config('services.stripe.secret'));

        // Buat Checkout Session (mode subscription)
        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [[
                'price'    => $price->provider_price_id, // Stripe Price ID (recurring)
                'quantity' => 1,
            ]],
            'metadata' => [
                'tenant_id' => $tenant->id,
                'user_id'   => $request->user()->id,
                'plan_id'   => $price->plan_id,
                'price_id'  => $price->id,
            ],
            'success_url' => $success.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancel.'?canceled=1',
        ]);

        return redirect()->away($session->url);
    }

    protected function subscribePayPal(Request $request, Price $price, string $currency)
    {
        $tenant = $request->user()->tenant;
        $success = config('billing.success_url');
        $cancel  = config('billing.cancel_url');

        $env = config('services.paypal.mode', 'sandbox') === 'live'
            ? new ProductionEnvironment(config('services.paypal.client_id'), config('services.paypal.secret'))
            : new SandboxEnvironment(config('services.paypal.client_id'), config('services.paypal.secret'));

        $client = new PayPalHttpClient($env);

        // price->provider_price_id harus berisi PayPal PLAN ID (bukan product id)
        $req = new SubscriptionsCreateRequest();
        $req->body = [
            'plan_id' => $price->provider_price_id,
            'custom_id' => $tenant->id, // biar mudah map tenant di webhook
            'application_context' => [
                'brand_name' => config('app.name'),
                'locale'     => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => $success,
                'cancel_url' => $cancel,
            ],
        ];

        $res = $client->execute($req);
        $links = collect($res->result->links ?? []);
        $approve = $links->firstWhere('rel', 'approve');
        if ($approve && isset($approve->href)) {
            return redirect()->away($approve->href);
        }

        return redirect()->route('billing')->with('error', 'PayPal approval link not found.');
    }

    public function invoice(Request $request, string $invoice)
    {
        $inv = Invoice::where('tenant_id', $request->user()->tenant_id)->where('id', $invoice)->firstOrFail();
        if ($inv->hosted_url) return redirect()->away($inv->hosted_url);
        if ($inv->pdf_url)    return redirect()->away($inv->pdf_url);
        return view('billing-invoice', compact('inv'));
    }
}
