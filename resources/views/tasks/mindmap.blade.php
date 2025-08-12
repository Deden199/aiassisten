@extends('layouts.app')

@section('title', 'Mindmap Task')

@section('header')
  <h2 class="font-semibold text-xl text-gray-800">Mindmap</h2>
@endsection

@section('content')
  <div class="max-w-7xl mx-auto px-6 py-8 space-y-6">
    @if (session('ok'))
      <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3">
        {{ session('ok') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3">
        {{ $errors->first() }}
      </div>
    @endif

    <p class="text-gray-700">Queue a mindmap generation task for this project.</p>

    <form action="{{ route('tasks.mindmap', $project) }}" method="POST" class="space-y-4">
      @csrf
      <button class="px-4 py-2 rounded-xl bg-fuchsia-600 text-white font-semibold hover:opacity-90 transition">
        Generate Mindmap
      </button>
    </form>

    <a href="{{ route('dashboard') }}" class="text-sm text-violet-600 underline">Back to dashboard</a>
  </div>
@endsection
