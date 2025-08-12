<nav x-data="{ open: false }" class="bg-gradient-to-r from-violet-700 via-fuchsia-600 to-rose-600 text-white shadow-lg">
  <!-- Primary Navigation Menu -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16">
      <div class="flex items-center">
        <!-- Logo -->
        <div class="shrink-0 flex items-center">
          <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="flex items-center gap-2">
            <span class="inline-flex w-8 h-8 rounded-xl bg-white text-violet-700 font-bold items-center justify-center">AI</span>
            <span class="font-semibold hidden sm:inline">AI Assistant</span>
          </a>
        </div>

        <!-- Navigation Links -->
        @auth
          <div class="hidden sm:flex sm:items-center sm:ms-10 gap-6">
            <a href="{{ route('dashboard') }}" class="text-white/90 hover:text-yellow-300 font-medium {{ request()->routeIs('dashboard') ? 'underline decoration-yellow-300' : '' }}">Dashboard</a>
            <a href="{{ route('projects.index') }}" class="text-white/90 hover:text-yellow-300 font-medium {{ request()->routeIs('projects.*') ? 'underline decoration-yellow-300' : '' }}">Projects</a>
            <a href="{{ route('billing') }}" class="text-white/90 hover:text-yellow-300 font-medium {{ request()->routeIs('billing') ? 'underline decoration-yellow-300' : '' }}">Billing</a>
            @can('admin')
              <a href="{{ route('admin.slide-templates.index') }}" class="text-white/90 hover:text-yellow-300 font-medium {{ request()->routeIs('admin.slide-templates.*') ? 'underline decoration-yellow-300' : '' }}">Slide Templates</a>
            @endcan
            <a href="{{ route('chat') }}" class="text-white/90 hover:text-yellow-300 font-medium {{ request()->routeIs('chat') ? 'underline decoration-yellow-300' : '' }}">Chatbot</a>
          </div>
        @else
          <div class="hidden sm:flex sm:items-center sm:ms-10 gap-6">
            <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="text-white/90 hover:text-yellow-300 font-medium">{{ __('Log in') }}</a>
            @if (Route::has('register'))
              <a href="{{ route('register') }}" class="text-white/90 hover:text-yellow-300 font-medium">{{ __('Register') }}</a>
            @endif
          </div>
        @endauth

        <!-- Locale Selector -->
        <div class="hidden sm:flex sm:items-center sm:ms-6">
          <form method="POST" action="{{ route('locale') }}">
            @csrf
            <select name="locale" onchange="this.form.submit()" class="bg-white/10 text-white rounded-md px-2 py-1">
              @foreach (config('app.available_locales') as $code)
                <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $code }}</option>
              @endforeach
            </select>
          </form>
        </div>
      </div>

      <!-- Settings / Auth -->
      @auth
        <div class="hidden sm:flex sm:items-center sm:ms-6">
          <div class="me-3" x-data="{ openC:false }">
            <button @click="openC=!openC" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 ring-1 ring-white/20 hover:bg-white/15">
              <span class="text-sm font-semibold">Create</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
            </button>
            <div x-show="openC" @click.outside="openC=false" x-transition
                 class="absolute right-4 mt-2 w-48 rounded-lg bg-white text-gray-800 shadow-lg ring-1 ring-black/5 overflow-hidden">
              <a href="#"
                 onclick="document.querySelector('[x-data] [x-cloak]')?.__x?.set('openNew', true); return false;"
                 class="block px-4 py-2 hover:bg-gray-50">New Project</a>
              @if (Route::has('dashboard'))
              <a href="{{ route('dashboard') }}#new-project" class="block px-4 py-2 hover:bg-gray-50">Upload File</a>
              @endif
            </div>
          </div>

          <x-dropdown align="right" width="48">
            <x-slot name="trigger">
              <button class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-white/90 bg-white/10 ring-1 ring-white/20 hover:bg-white/15 focus:outline-none transition">
                <div>{{ Auth::user()->name }}</div>
                <div class="ms-1">
                  <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </div>
              </button>
            </x-slot>

            <x-slot name="content">
              @if (Route::has('profile.edit'))
                <x-dropdown-link :href="route('profile.edit')">
                  {{ __('Profile') }}
                </x-dropdown-link>
              @endif

              @if (Route::has('billing'))
                <x-dropdown-link :href="route('billing')">
                  {{ __('Billing') }}
                </x-dropdown-link>
              @endif

              <div class="border-t my-1"></div>

              @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <x-dropdown-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                  </x-dropdown-link>
                </form>
              @else
                <form method="POST" action="{{ url('/logout') }}">
                  @csrf
                  <x-dropdown-link href="{{ url('/logout') }}"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                  </x-dropdown-link>
                </form>
              @endif
            </x-slot>
          </x-dropdown>
        </div>
      @endauth

      <!-- Hamburger -->
      <div class="-me-2 flex items-center sm:hidden">
        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white/90 hover:text-white hover:bg-white/10 focus:outline-none transition">
          <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Responsive Navigation Menu -->
  <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
    @auth
      <div class="pt-2 pb-3 space-y-1 px-4">
        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">Dashboard</a>
        <a href="{{ route('projects.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">Projects</a>
        <a href="{{ route('billing') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">Billing</a>
        @can('admin')
          <a href="{{ route('admin.slide-templates.index') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">Slide Templates</a>
        @endcan
        <a href="{{ route('chat') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">Chatbot</a>
      </div>

      <form method="POST" action="{{ route('locale') }}" class="pb-3 px-4">
        @csrf
        <select name="locale" onchange="this.form.submit()" class="w-full rounded-lg bg-white/10 text-white">
          @foreach (config('app.available_locales') as $code)
            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $code }}</option>
          @endforeach
        </select>
      </form>

      <!-- Responsive Settings Options -->
        <div class="pt-4 pb-4 border-t border-white/10 px-4">
        <div class="px-3">
          <div class="font-medium text-base text-white/95">{{ Auth::user()->name }}</div>
          <div class="font-medium text-sm text-white/80">{{ Auth::user()->email }}</div>
        </div>

        <div class="mt-3 space-y-1">
          @if (Route::has('profile.edit'))
          <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">
            {{ __('Profile') }}
          </a>
          @endif

          @if (Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <a href="{{ route('logout') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10"
                 onclick="event.preventDefault(); this.closest('form').submit();">
                {{ __('Log Out') }}
              </a>
            </form>
          @else
            <form method="POST" action="{{ url('/logout') }}">
              @csrf
              <a href="{{ url('/logout') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10"
                 onclick="event.preventDefault(); this.closest('form').submit();">
                {{ __('Log Out') }}
              </a>
            </form>
          @endif
        </div>
      </div>
    @else
      <div class="pt-2 pb-3 space-y-1 px-4">
        <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">
          {{ __('Log in') }}
        </a>
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="block px-3 py-2 rounded-lg hover:bg-white/10">
            {{ __('Register') }}
          </a>
        @endif
      </div>

      <form method="POST" action="{{ route('locale') }}" class="pb-3 px-4">
        @csrf
        <select name="locale" onchange="this.form.submit()" class="w-full rounded-lg bg-white/10 text-white">
          @foreach (config('app.available_locales') as $code)
            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $code }}</option>
          @endforeach
        </select>
      </form>
    @endauth
  </div>
</nav>
