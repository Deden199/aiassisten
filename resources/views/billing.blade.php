@extends('layouts.app')

@section('title', 'Billing')

@section('header')
  <h2 class="font-semibold text-xl text-gray-800">Billing</h2>
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-6 lg:px-8 py-8 space-y-8">

  @if (session('success'))
    <div class="rounded border border-green-300 bg-green-50 text-green-800 px-4 py-3 text-sm">
      {{ session('success') }}
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-2xl border bg-white p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Current Plan</h3>
      @if($subscription)
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xl font-bold">{{ optional($subscription->plan)->code ?? '—' }}</div>
            <div class="text-sm text-gray-600">
              Status: <span class="font-medium">{{ $subscription->status }}</span>
              @if($subscription->renews_at) • Renews: {{ $subscription->renews_at->format('M d, Y') }} @endif
            </div>
            <div class="text-xs text-gray-500">Provider: {{ $subscription->provider ?: 'manual' }}</div>
          </div>
          <div class="flex gap-2">
            <form method="POST" action="{{ route('billing.subscribe') }}">
              @csrf
              <input type="hidden" name="price_id" value="">
              <button type="button" disabled class="px-3 py-2 rounded border text-gray-400" title="Coming soon">Cancel</button>
            </form>
          </div>
        </div>
      @else
        <p class="text-sm text-gray-600">No active subscription.</p>
      @endif
    </div>

    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Usage</h3>
      <div class="text-sm text-gray-600">This section can show monthly token usage/cost.</div>
      <div class="mt-3 rounded border p-3 text-sm text-gray-500">Coming soon</div>
    </div>
  </div>

  <div class="rounded-2xl border bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">Plans & Pricing</h3>
    </div>

    @if($plans->isEmpty())
      <p class="text-sm text-gray-500">No prices configured yet.</p>
    @else
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($plans as $plan)
          <div class="rounded-xl border p-4">
            <div class="flex items-center justify-between">
              <div class="font-semibold text-gray-800">{{ strtoupper($plan->code) }}</div>
              <span class="text-xs rounded-full bg-gray-100 px-2 py-0.5 text-gray-600">Plan</span>
            </div>

            @if($plan->features)
              <ul class="mt-3 space-y-1 text-sm text-gray-600">
                @foreach((array)$plan->features as $f)
                  <li>• {{ $f }}</li>
                @endforeach
              </ul>
            @endif

            <div class="mt-4 space-y-3">
              @forelse($plan->prices as $price)
                <form method="POST" action="{{ route('billing.subscribe') }}" class="flex items-center justify-between rounded border p-3">
                  @csrf
                  <div>
                    <div class="text-sm font-medium">{{ ucfirst($price->interval ?? 'monthly') }}</div>
                    <div class="text-xs text-gray-500">{{ $price->currency ?? 'USD' }}</div>
                  </div>
                  <div class="text-right">
                    <div class="text-xl font-bold">
                      @php $amt = ($price->amount_cents ?? 0) / 100; @endphp
                      {{ ($price->currency ?? 'USD') }} {{ number_format($amt, 2) }}
                    </div>
                    <input type="hidden" name="price_id" value="{{ $price->id }}">
                    <button class="mt-1 px-3 py-1.5 rounded bg-indigo-600 text-white text-sm">Choose</button>
                  </div>
                </form>
              @empty
                <div class="rounded border p-3 text-sm text-gray-500">No active prices.</div>
              @endforelse
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <div class="rounded-2l border bg-white p-6 shadow-sm rounded-2xl">
    <h3 class="text-lg font-semibold mb-4">Invoice History</h3>
    @if($invoices->isEmpty())
      <p class="text-sm text-gray-500">No invoices yet.</p>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="text-left text-gray-500">
            <tr>
              <th class="py-2 pr-4">Invoice</th>
              <th class="py-2 pr-4">Date</th>
              <th class="py-2 pr-4">Amount</th>
              <th class="py-2 pr-4">Status</th>
              <th class="py-2">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoices as $inv)
              @php
                $amount = ($inv->amount_paid_cents ?? $inv->amount_due_cents ?? 0) / 100;
                $currency = $inv->currency ?? 'USD';
              @endphp
              <tr class="border-t">
                <td class="py-2 pr-4">#{{ $inv->id }}</td>
                <td class="py-2 pr-4">{{ optional($inv->paid_at ?? $inv->created_at)->format('M d, Y') }}</td>
                <td class="py-2 pr-4">{{ $currency }} {{ number_format($amount, 2) }}</td>
                <td class="py-2 pr-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px]
                    {{ ($inv->status ?? 'paid') === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $inv->status ?? 'paid' }}
                  </span>
                </td>
                <td class="py-2">
                  <a href="{{ route('billing.invoice', $inv->id) }}" class="text-indigo-600 hover:underline">View</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
