@extends('layouts.app')

@section('title', 'Slides Preview')

@section('content')
<div class="max-w-4xl mx-auto p-4 space-y-6">
    @foreach($version->payload ?? [] as $i => $slide)
        <div class="border rounded-lg p-4">
            <h3 class="font-medium">Slide {{ $i + 1 }}: {{ $slide['title'] ?? 'Untitled' }}</h3>
            @if(!empty($slide['bullets']))
                <ul class="mt-2 list-disc ml-5 space-y-1">
                    @foreach($slide['bullets'] as $b)
                        <li>{{ $b }}</li>
                    @endforeach
                </ul>
            @endif
            @if(!empty($slide['notes']))
                <p class="mt-2 text-sm text-gray-600">{{ $slide['notes'] }}</p>
            @endif
        </div>
    @endforeach
</div>
@endsection
