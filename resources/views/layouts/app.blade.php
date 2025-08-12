<!doctype html>
<html lang="{{ app()->getLocale() }}" @if(app()->getLocale()==='ar') dir="rtl" @endif>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title','AI Assistant')</title>
  <meta name="description" content="@yield('meta_description','')">
  <link rel="canonical" href="@yield('canonical', url()->current())">
  <meta property="og:title" content="@yield('og_title', View::yieldContent('title','AI Assistant'))">
  <meta property="og:description" content="@yield('og_description', View::yieldContent('meta_description',''))">
  <meta property="og:url" content="@yield('og_url', url()->current())">
  <meta property="og:type" content="@yield('og_type','website')">
  @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
  @endif
  <meta name="twitter:card" content="@yield('twitter_card','summary_large_image')">
  <meta name="twitter:title" content="@yield('twitter_title', View::yieldContent('og_title', View::yieldContent('title','AI Assistant')))">
  <meta name="twitter:description" content="@yield('twitter_description', View::yieldContent('og_description', View::yieldContent('meta_description','')))">
  @hasSection('twitter_image')
    <meta name="twitter:image" content="@yield('twitter_image')">
  @endif
  @stack('meta')
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
