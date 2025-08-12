<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['tenant','plan'])->paginate();
        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function edit(Subscription $subscription)
    {
        $plans = Plan::pluck('code', 'id');
        return view('admin.subscriptions.edit', compact('subscription', 'plans'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'required|string',
        ]);

        $subscription->update($data);

        return redirect()->route('admin.subscriptions.index')->with('status', 'Subscription updated');
    }
}
