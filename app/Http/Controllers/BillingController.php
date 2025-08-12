<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Subscriptions\SubscriptionsCreateRequest;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $subscription = $request->user()->tenant->subscription ?? null;
        $invoices = Invoice::where('tenant_id', $request->user()->tenant_id)->latest()->get();
        $prices = Price::with('plan')->where('is_active', true)->get();

        return view('billing', compact('subscription', 'invoices', 'prices'));
    }

    public function subscribe(Request $request)
    {
        $request->validate(['price_id' => 'required|uuid']);
        $price = Price::with('plan')->where('id', $request->input('price_id'))->where('is_active', true)->firstOrFail();
        $tenant = $request->user()->tenant;

        // Upsert local subscription immediately to lock intent
        $sub = Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id'              => $price->plan_id,
                'provider'             => $price->provider ?? 'stripe',
                'status'               => 'active',
                'current_period_start' => now(),
                'current_period_end'   => now()->addMonth(),
                'cancel_at_period_end' => false,
            ]
        );

        // Stripe checkout
        if (($price->provider ?? 'stripe') === 'stripe') {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $session = $stripe->checkout->sessions->create([
                'mode' => 'subscription',
                'line_items' => [['price' => $price->provider_price_id, 'quantity' => 1]],
                'client_reference_id' => $tenant->id,
                'success_url' => config('billing.success_url'),
                'cancel_url'  => config('billing.cancel_url'),
                'subscription_data' => [
                    'metadata' => ['tenant_id' => $tenant->id, 'plan_id' => $price->plan_id],
                ],
            ]);
            return redirect($session->url);
        }

        // PayPal
        $environment = new SandboxEnvironment(config('services.paypal.client_id'), config('services.paypal.secret'));
        $client = new PayPalHttpClient($environment);
        $pp = new SubscriptionsCreateRequest();
        $pp->body = [
            'plan_id' => $price->provider_price_id,
            'application_context' => [
                'return_url' => config('billing.success_url'),
                'cancel_url' => config('billing.cancel_url'),
            ],
            'custom_id' => $tenant->id,
        ];
        $response = $client->execute($pp);
        $approveUrl = collect($response->result->links ?? [])->firstWhere('rel', 'approve')->href ?? config('billing.cancel_url');
        return redirect($approveUrl);
    }

    public function invoice(Request $request, string $invoice)
    {
        $inv = Invoice::where('tenant_id', $request->user()->tenant_id)->where('id', $invoice)->first();
        if ($inv && $inv->hosted_url) return redirect($inv->hosted_url);
        if ($inv && $inv->pdf_url)    return redirect($inv->pdf_url);
        return redirect()->route('billing');
    }
}
