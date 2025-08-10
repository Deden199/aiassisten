@extends('layouts.app')
@section('title', 'AI Assistant – Do more with your study')
@section('content')
  <div class="max-w-5xl mx-auto py-16">
    <h1 class="text-4xl font-bold mb-4">{{ __('ui.welcome_title') }}</h1>
    <p class="text-muted-foreground mb-8">{{ __('ui.welcome_copy') }}</p>
    <a href="/app" class="inline-flex px-4 py-2 rounded-lg bg-primary text-primary-foreground">{{ __('ui.get_started') }}</a>
  </div>
@endsection