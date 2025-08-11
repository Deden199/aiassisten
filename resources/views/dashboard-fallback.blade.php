@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Dashboard</h1>
    <div class="alert alert-warning">
        {{ $message ?? 'Dashboard sementara tidak dapat dimuat.' }}
    </div>
</div>
@endsection
