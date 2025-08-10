@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
@endsection

@section('content')
<div class="py-12">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
      <div class="grid grid-cols-1 sm:grid-cols-5 text-center gap-4">
        <div><div class="text-2xl font-bold">{{ $tenantsCount }}</div><div class="text-sm text-gray-500">Tenants</div></div>
        <div><div class="text-2xl font-bold">{{ $usersCount }}</div><div class="text-sm text-gray-500">Users</div></div>
        <div><div class="text-2xl font-bold">{{ $plansCount }}</div><div class="text-sm text-gray-500">Plans</div></div>
        <div><div class="text-2xl font-bold">{{ $subscriptionsCount }}</div><div class="text-sm text-gray-500">Subscriptions</div></div>
        <div><div class="text-2xl font-bold">{{ $licensesCount }}</div><div class="text-sm text-gray-500">Licenses</div></div>
      </div>
    </div>
  </div>
</div>
@endsection
