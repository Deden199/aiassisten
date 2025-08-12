@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8">
  <h1 class="text-2xl font-bold mb-6">License</h1>

  @if (session('status'))
    <div class="p-3 bg-green-100 border border-green-300 rounded mb-4">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="p-3 bg-red-100 border border-red-300 rounded mb-4">
      <ul class="list-disc pl-6">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white rounded shadow p-6">
    <p class="mb-4">Status: <strong>{{ strtoupper($license->status) }}</strong>
      @if($license->status === 'grace' && $license->grace_until) (until {{ $license->grace_until->toDateString() }}) @endif
    </p>

    <form method="post" action="{{ route('admin.licenses.update', $license) }}" class="space-y-3">
      @csrf
      @method('PUT')
      <input type="text" name="purchase_code" class="border p-2 w-full" placeholder="Purchase Code" required>
      <input type="text" name="domain" class="border p-2 w-full" placeholder="Domain (example.com)" required>
      <button class="bg-black text-white px-4 py-2 rounded">Verify & Activate</button>
    </form>

    <form method="post" action="{{ route('admin.licenses.deactivate', $license) }}" class="mt-4">
      @csrf
      <button class="px-4 py-2 border rounded">Deactivate</button>
    </form>
  </div>
</div>
@endsection
