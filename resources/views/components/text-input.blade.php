@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-violet-300 focus:border-fuchsia-500 focus:ring-rose-500 rounded-md shadow-sm']) }}>
