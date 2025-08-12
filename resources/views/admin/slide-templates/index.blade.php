@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">Slide Templates</h2>
@endsection

@section('content')
<div class="py-6">
  <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
    <div class="flex justify-end mb-4">
      <a href="{{ route('admin.slide-templates.create') }}" class="px-4 py-2 rounded bg-indigo-600 text-white">New Template</a>
    </div>
    @if (session('ok'))
      <div class="mb-4 p-3 rounded bg-emerald-100 text-emerald-800">{{ session('ok') }}</div>
    @endif
    <div class="bg-white shadow-sm rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preview</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @foreach($templates as $tpl)
          @php
            $bg = data_get($tpl,'background_default.type') === 'gradient'
                ? 'linear-gradient(to bottom right, '.data_get($tpl,'background_default.gradient.from','#ffffff').', '.data_get($tpl,'background_default.gradient.to','#ffffff').')'
                : data_get($tpl,'background_default.color', data_get($tpl,'palette.background','#ffffff'));
          @endphp
          <tr>
            <td class="px-6 py-4">{{ $tpl->name }}</td>
            <td class="px-6 py-4">
              <div class="w-24 h-16 rounded border overflow-hidden" style="background: {{ $bg }}">
                <div class="h-2 w-full" style="background: {{ data_get($tpl,'palette.accent','#000000') }}"></div>
              </div>
            </td>
            <td class="px-6 py-4 text-right space-x-2">
              <form action="{{ route('admin.slide-templates.duplicate', $tpl) }}" method="POST" class="inline">@csrf<button class="px-2 py-1 border rounded">Duplicate</button></form>
              <a href="{{ route('admin.slide-templates.edit', $tpl) }}" class="px-2 py-1 border rounded">Edit</a>
              <form action="{{ route('admin.slide-templates.destroy', $tpl) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">
                @csrf @method('DELETE')
                <button class="px-2 py-1 border rounded text-rose-600">Delete</button>
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
