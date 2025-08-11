<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\Invoice;
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
        $invoices = Invoice::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->get();

        return view('billing', compact('subscription', 'invoices'));
    }

    public function subscribe(Request $request)
    {
        $price = Price::where('id', $request->input('price_id'))
            ->where('is_active', true)
            ->firstOrFail();
        $tenant = $request->user()->tenant;

        if ($price->provider === 'paypal') {
            $environment = new SandboxEnvironment(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            );
            $client = new PayPalHttpClient($environment);

            $ppRequest = new SubscriptionsCreateRequest();
            $ppRequest->body = [
                'plan_id' => $price->provider_price_id,
                'application_context' => [
                    'return_url' => config('billing.success_url'),
                    'cancel_url' => config('billing.cancel_url'),
                ],
                'custom_id' => $tenant->id,
            ];
            $response = $client->execute($ppRequest);
            $approveUrl = collect($response->result->links ?? [])
                ->firstWhere('rel', 'approve')->href ?? config('billing.cancel_url');

            return redirect($approveUrl);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [
                [
                    'price' => $price->provider_price_id,
                    'quantity' => 1,
                ],
            ],
            'client_reference_id' => $tenant->id,
            'success_url' => config('billing.success_url'),
            'cancel_url' => config('billing.cancel_url'),
        ]);

        return redirect($session->url);
    }

    public function invoice(Request $request, string $invoice)
    {
        $inv = Invoice::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $invoice)
            ->first();

        if ($inv && $inv->hosted_url) {
            return redirect($inv->hosted_url);
        }

        if ($inv && $inv->pdf_url) {
            return redirect($inv->pdf_url);
        }

        return redirect()->route('billing');
    }
}
