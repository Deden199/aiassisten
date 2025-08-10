@extends('layouts.app')

@section('title', 'Billing')

@section('header')
  <h2 class="font-semibold text-xl text-gray-800">Billing</h2>
@endsection

@section('content')
  <div class="max-w-3xl mx-auto px-6 lg:px-8 py-8 space-y-8">
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Current Plan</h3>
      @if($subscription)
        <p>{{ $subscription->plan->code }} ({{ $subscription->status }})</p>
        <p class="text-sm text-gray-500">Renews {{ optional($subscription->current_period_end)->toDateString() }}</p>
      @else
        <p>No active subscription.</p>
      @endif
    </div>
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Invoice History</h3>
      @forelse($invoices as $inv)
        <div class="flex items-center justify-between py-2 border-b last:border-0">
          <span>#{{ $inv['id'] ?? $inv->id }} - {{ isset($inv['amount_due']) ? number_format(($inv['amount_due'] ?? 0)/100, 2) : '' }}</span>
          <a href="{{ route('billing.invoice', $inv['id'] ?? $inv->id) }}" class="text-violet-600 hover:underline">View</a>
        </div>
      @empty
        <p class="text-sm text-gray-500">No invoices yet.</p>
      @endforelse
    </div>
  </div>
@endsection
