@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
@endsection

@section('content')
<div class="py-6">
  <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow-sm rounded-lg">
      <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')
        <div class="grid gap-4">
          <div>
            <label class="block mb-1">Role</label>
            <select name="role" class="border rounded px-3 py-2">
              @foreach($roles as $role)
              <option value="{{ $role }}" @selected(old('role', $user->role) == $role)>{{ ucfirst($role) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block mb-1">Tenant</label>
            <select name="tenant_id" class="border rounded px-3 py-2">
              <option value="">-</option>
              @foreach($tenants as $tenant)
              <option value="{{ $tenant->id }}" @selected(old('tenant_id', $user->tenant_id) == $tenant->id)>{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
          <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border rounded">Cancel</a>
          <button class="px-4 py-2 rounded bg-indigo-600 text-white">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
