@extends('layouts.app')

@section('title', 'Invoice')

@section('header')
  <h2 class="font-semibold text-xl text-gray-800">Invoice #{{ $inv->id }}</h2>
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-6 lg:px-8 py-8 space-y-6">
  <div class="rounded-2xl border bg-white p-6 shadow-sm">
    <div class="text-sm text-gray-600">Provider: {{ $inv->provider ?? 'manual' }}</div>
    <div class="text-sm text-gray-600">Date: {{ optional($inv->paid_at ?? $inv->created_at)->format('M d, Y') }}</div>
    @php
      $amount = ($inv->amount_paid_cents ?? $inv->amount_due_cents ?? 0)/100;
      $currency = $inv->currency ?? 'USD';
    @endphp
    <div class="mt-2 text-lg font-semibold">{{ $currency }} {{ number_format($amount, 2) }}</div>
    <div class="mt-2 text-sm">
      Status: <span class="font-medium">{{ $inv->status ?? 'paid' }}</span>
    </div>
  </div>
</div>
@endsection
