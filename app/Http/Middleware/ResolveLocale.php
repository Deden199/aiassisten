<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ResolveLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale');
        $available = config('app.available_locales', []);

        if (! $locale && $request->query('locale')) {
            $locale = $request->query('locale');
        }

        if (! $locale && ($user = Auth::user()) && $user->locale) {
            $locale = $user->locale;
        }

        if (! $locale && $request->cookie('locale')) {
            $locale = $request->cookie('locale');
        }

        if (! $locale) {
            $locale = $request->getPreferredLanguage($available) ?: config('app.locale');
        }

        if (! in_array($locale, $available)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        Cookie::queue('locale', $locale, 60 * 24 * 365);

        return $next($request);
    }
}
