@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="max-w-6xl mx-auto p-6">
  <h2 class="text-2xl font-semibold mb-6">{{ __('ui.dashboard') }}</h2>
  <div class="grid md:grid-cols-3 gap-4">
    <x-ui.card>
      <x-slot:title>{{ __('ui.quick_start') }}</x-slot:title>
      <p class="text-sm text-muted-foreground mb-4">{{ __('ui.quick_start_copy') }}</p>
      <a href="#" class="px-3 py-2 rounded bg-primary text-primary-foreground">{{ __('ui.new_project') }}</a>
    </x-ui.card>
    <x-ui.card>
      <x-slot:title>{{ __('ui.credits') }}</x-slot:title>
      <p>—</p>
    </x-ui.card>
    <x-ui.card>
      <x-slot:title>{{ __('ui.recent') }}</x-slot:title>
      <p>—</p>
    </x-ui.card>
  </div>
</div>
@endsection