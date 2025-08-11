@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'icon' => null,
    'iconRight' => null,
])
@php
$base = 'inline-flex items-center justify-center rounded-md font-semibold focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition';
$variants = [
    'primary' => 'bg-primary text-primary-foreground hover:bg-primary/90',
    'secondary' => 'bg-secondary text-primary-foreground hover:bg-secondary/90',
    'ghost' => 'bg-transparent hover:bg-muted text-primary',
    'link' => 'bg-transparent underline-offset-4 text-primary hover:underline',
    'danger' => 'bg-danger text-primary-foreground hover:bg-danger/90',
];
$sizes = [
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-8 text-lg',
];
@endphp
<button {{ $attributes->merge(['class' => $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md'])]) }}>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8" />
        </svg>
    @endif
    @if($icon)
        <x-ui.icon :name="$icon" class="mr-2" />
    @endif
    {{ $slot }}
    @if($iconRight)
        <x-ui.icon :name="$iconRight" class="ml-2" />
    @endif
</button>
