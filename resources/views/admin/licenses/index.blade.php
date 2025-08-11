@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">Licenses</h2>
@endsection

@section('content')
<div class="py-12">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3">Update</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @foreach($licenses as $license)
          <tr>
            <td class="px-6 py-4 whitespace-nowrap">{{ optional($license->tenant)->name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $license->purchase_code }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $license->status }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <form method="POST" action="{{ route('admin.licenses.update', $license) }}" class="flex items-center space-x-2">
                @csrf
                @method('PUT')
                <select name="status" class="border-gray-300 rounded">
                  <option value="active" @selected($license->status === 'active')>active</option>
                  <option value="inactive" @selected($license->status === 'inactive')>inactive</option>
                </select>
                <button type="submit" class="px-2 py-1 bg-indigo-600 text-white rounded">Save</button>
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
