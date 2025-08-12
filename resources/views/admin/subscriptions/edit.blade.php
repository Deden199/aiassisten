@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Subscription</h2>
@endsection

@section('content')
<div class="py-12">
  <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
      @if(session('status'))
        <div class="mb-4 p-3 rounded bg-emerald-100 text-emerald-800">{{ session('status') }}</div>
      @endif
      @if($errors->any())
        <div class="mb-4 p-3 rounded bg-rose-100 text-rose-800">{{ $errors->first() }}</div>
      @endif
      <form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="grid gap-4">
          <select name="plan_id" class="border rounded px-3 py-2">
            <option value="">-- Select Plan --</option>
            @foreach($plans as $id => $code)
              <option value="{{ $id }}" @selected(old('plan_id', $subscription->plan_id) == $id)>{{ $code }}</option>
            @endforeach
          </select>
          <input type="text" name="status" value="{{ old('status', $subscription->status) }}" class="border rounded px-3 py-2" placeholder="Status" required>
        </div>
        <div class="mt-6 flex justify-end gap-2">
          <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 border rounded">Cancel</a>
          <button class="px-4 py-2 rounded bg-indigo-600 text-white">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

