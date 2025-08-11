<!doctype html>
<html lang="{{ app()->getLocale() }}" @if(app()->getLocale()==='ar') dir="rtl" @endif>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title','AI Assistant')</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('head')
</head>
<body class="min-h-dvh bg-white text-gray-900">
  <div class="min-h-dvh">
    @includeIf('layouts.navigation')

    @hasSection('header')
      <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          @yield('header')
        </div>
      </header>
    @endif

    <main>
      @yield('content')
    </main>
  </div>

  @stack('scripts')
</body>
</html>
