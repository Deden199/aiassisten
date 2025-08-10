<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
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

        if (in_array($event->type, [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
        ])) {
            $data = $event->data->object;
            $subscription = Subscription::firstOrNew([
                'tenant_id' => $data->metadata->tenant_id ?? null,
            ]);
            $subscription->gateway = 'stripe';
            $subscription->plan_id = $data->metadata->plan_id ?? $subscription->plan_id;
            $subscription->gateway_sub_id = $data->id;
            $subscription->status = $data->status;
            $subscription->current_period_start = date('Y-m-d H:i:s', $data->current_period_start);
            $subscription->current_period_end = date('Y-m-d H:i:s', $data->current_period_end);
            $subscription->save();
        }

        return response()->json(['status' => 'ok']);
    }

    public function paypal(Request $request)
    {
        $event = $request->all();
        $resource = $event['resource'] ?? null;

        if ($resource && isset($resource['id'])) {
            $subscription = Subscription::firstOrNew([
                'tenant_id' => $resource['custom_id'] ?? null,
            ]);
            $subscription->gateway = 'paypal';
            $subscription->gateway_sub_id = $resource['id'];
            $subscription->status = $event['event_type'] === 'BILLING.SUBSCRIPTION.CANCELLED'
                ? 'canceled'
                : 'active';
            $subscription->current_period_start = now();
            $subscription->current_period_end = now();
            $subscription->save();
        }

        return response()->json(['status' => 'ok']);
    }
}
