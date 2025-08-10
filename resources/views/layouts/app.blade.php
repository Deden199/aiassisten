<!doctype html>
<html lang="{{ app()->getLocale() }}" @if(app()->getLocale()==='ar') dir="rtl" @endif>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title','AI Assistant')</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-dvh bg-background text-foreground">
  <header class="border-b">
    <div class="max-w-6xl mx-auto p-4 flex items-center justify-between">
      <a href="/" class="font-bold">AI Assistant</a>
      <nav class="flex items-center gap-4">
        <a href="/app" class="hover:underline">Dashboard</a>
      </nav>
    </div>
  </header>
  <main>@yield('content')</main>
</body>
</html>