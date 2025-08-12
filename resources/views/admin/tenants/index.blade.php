@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">Tenants</h2>
@endsection

@section('content')
<div class="py-12">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
            <th class="px-6 py-3">Active</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @foreach($tenants as $tenant)
          <tr>
            <td class="px-6 py-4 whitespace-nowrap">{{ $tenant->name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $tenant->users_count }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $tenant->is_active ? 'Yes' : 'No' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
