@props(['name', 'class' => ''])
@php
$paths = [
    'check' => '<polyline points="20 6 9 17 4 12" />',
    'x' => '<line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />',
    'loader' => '<path d="M12 2v4m0 12v4m10-10h-4m-12 0H2m16.95-6.95l-2.83 2.83M7.88 16.12l-2.83 2.83m0-13.66 2.83 2.83m13.66 0-2.83 2.83" />',
];
@endphp
<svg class="w-4 h-4 {{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    {!! $paths[$name] ?? '' !!}
</svg>
