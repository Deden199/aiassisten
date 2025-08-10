<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @if($isRtl ?? false) dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <title>{{ __t('Home') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.4/dist/tailwind.min.css">
</head>
<body class="antialiased">
<nav class="p-4 border-b flex justify-between">
    <a href="/{{ app()->getLocale() }}" class="font-bold">{{ __t('Home') }}</a>
    <form>
        <select name="locale" onchange="this.form.submit()" class="border p-1">
            @foreach(config('app.available_locales') as $loc)
                <option value="{{ $loc }}" @selected(app()->getLocale()==$loc)>{{ $loc }}</option>
            @endforeach
        </select>
    </form>
</nav>
<div class="p-6">
    @yield('content')
</div>
</body>
</html>
