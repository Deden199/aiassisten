@props(['padding' => true])
<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-md']) }}>
    @isset($header)
        <div class="border-b border-border px-4 py-2">{{ $header }}</div>
    @endisset
    <div class="{{ $padding ? 'p-4' : '' }}">
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="border-t border-border px-4 py-2">{{ $footer }}</div>
    @endisset
</div>
