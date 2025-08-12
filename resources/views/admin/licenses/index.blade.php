@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">Licenses</h2>
@endsection

@section('content')
<div class="py-12">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
      @if(session('status'))
        <div class="mb-4 p-3 rounded bg-emerald-100 text-emerald-800">{{ session('status') }}</div>
      @endif
      @if($errors->any())
        <div class="mb-4 p-3 rounded bg-rose-100 text-rose-800">{{ $errors->first() }}</div>
      @endif
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3">Verify</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @foreach($licenses as $license)
          <tr>
            <td class="px-6 py-4 whitespace-nowrap">{{ optional($license->tenant)->name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $license->purchase_code }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $license->domain }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $license->status }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <form method="POST" action="{{ route('admin.licenses.update', $license) }}" class="flex items-center space-x-2">
                @csrf
                @method('PUT')
                <input type="text" name="purchase_code" class="border-gray-300 rounded" placeholder="Purchase code">
                <input type="text" name="domain" class="border-gray-300 rounded" placeholder="Domain" value="{{ old('domain') }}">
                <button type="submit" class="px-2 py-1 bg-indigo-600 text-white rounded">Verify</button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
