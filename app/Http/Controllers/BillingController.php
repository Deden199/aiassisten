<?php

namespace App\Http\Controllers;

use App\Models\Plan;
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
        $invoices = [];

        if ($subscription && $subscription->gateway === 'stripe' && $subscription->gateway_sub_id) {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $stripeInvoices = $stripe->invoices->all([
                'subscription' => $subscription->gateway_sub_id,
            ]);
            $invoices = $stripeInvoices->data ?? [];
        }

        return view('billing', compact('subscription', 'invoices'));
    }

    public function checkout(Request $request)
    {
        $plan = Plan::findOrFail($request->input('plan_id'));
        $tenant = $request->user()->tenant;
        $gateway = $request->input('gateway', 'stripe');

        if ($gateway === 'paypal') {
            $environment = new SandboxEnvironment(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            );
            $client = new PayPalHttpClient($environment);

            $ppRequest = new SubscriptionsCreateRequest();
            $ppRequest->body = [
                'plan_id' => $request->input('paypal_plan_id'),
                'application_context' => [
                    'return_url' => route('billing'),
                    'cancel_url' => route('billing'),
                ],
                'custom_id' => $tenant->id,
            ];
            $response = $client->execute($ppRequest);

            return redirect($response->result->links[0]->href ?? route('billing'));
        }

        $price = $plan->prices()->first();

        $stripe = new StripeClient(config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $price->currency,
                        'unit_amount' => $price->amount_cents,
                        'product_data' => ['name' => $plan->code],
                        'recurring' => ['interval' => 'month'],
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                ],
            ],
            'success_url' => route('billing'),
            'cancel_url' => route('billing'),
        ]);

        return redirect($session->url);
    }

    public function invoice(Request $request, string $invoice)
    {
        $subscription = $request->user()->tenant->subscription ?? null;

        if ($subscription && $subscription->gateway === 'stripe') {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $inv = $stripe->invoices->retrieve($invoice, []);
            return redirect($inv->hosted_invoice_url);
        }

        return redirect()->route('billing');
    }
}
