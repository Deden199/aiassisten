@extends('layouts.app')

@section('title', 'Billing')

@section('header')
  <h2 class="font-semibold text-xl text-gray-800">Billing</h2>
@endsection

@section('content')
  <div class="max-w-5xl mx-auto px-6 lg:px-8 py-8 space-y-8">

    {{-- CURRENT PLAN --}}
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Current Plan</h3>
      @if($subscription)
        <p>{{ optional($subscription->plan)->code ?? '—' }} ({{ $subscription->status }})</p>
        <p class="text-sm text-gray-500">Renews {{ optional($subscription->current_period_end)->toDateString() }}</p>
      @else
        <p>No active subscription.</p>
      @endif
    </div>

    {{-- PLANS & PRICING --}}
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Plans & Pricing</h3>
      @if(($prices ?? collect())->isEmpty())
        <p class="text-sm text-gray-500">No prices configured yet.</p>
      @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          @foreach($prices as $price)
            <div class="rounded-xl border p-5 flex flex-col justify-between">
              <div>
                <div class="text-xs uppercase text-gray-500">{{ strtoupper($price->currency) }}</div>
                <div class="mt-1 text-2xl font-bold">
                  {{ number_format(($price->amount_cents ?? 0)/100, 2) }}
                  <span class="text-sm font-normal text-gray-500">/ {{ $price->interval ?? 'month' }}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                  Plan: <span class="font-medium">{{ optional($price->plan)->code }}</span>
                </div>
                @php $features = optional($price->plan)->features ?? []; @endphp
                <ul class="mt-3 text-sm text-gray-600 space-y-1">
                  @foreach(($features ?? []) as $k => $v)
                    <li>• {{ strtoupper($k) }}{{ is_numeric($v) ? ': '.$v : '' }}</li>
                  @endforeach
                </ul>
              </div>

              <form class="mt-5" method="POST" action="{{ route('billing.subscribe') }}">
                @csrf
                <input type="hidden" name="price_id" value="{{ $price->id }}">
                <button class="w-full px-4 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition">
                  @if($subscription && $subscription->plan_id === $price->plan_id)
                    Current Plan
                  @else
                    Subscribe
                  @endif
                </button>
              </form>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- INVOICES --}}
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Invoice History</h3>
      @forelse($invoices as $inv)
        <div class="flex items-center justify-between py-2 border-b last:border-0">
          <span>#{{ $inv->id }} — {{ number_format(($inv->amount_due_cents ?? 0)/100, 2) }} {{ strtoupper($inv->currency ?? config('billing.default_currency')) }}</span>
          <a href="{{ route('billing.invoice', $inv->id) }}" class="text-violet-600 hover:underline">View</a>
        </div>
      @empty
        <p class="text-sm text-gray-500">No invoices yet.</p>
      @endforelse
    </div>
  </div>
@endsection
