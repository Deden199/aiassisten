<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class ResolveLocale
{
    public function handle(Request $request, Closure $next)
    {
        $available = config('app.available_locales', []);
        $locale = null;

        if (($user = Auth::user()) && $user->locale) {
            $locale = $user->locale;
        }

        if (! $locale && $request->cookie('locale')) {
            $locale = $request->cookie('locale');
        }

        if (! $locale && $request->route('locale')) {
            $locale = $request->route('locale');
        }

        if (! $locale && $request->query('locale')) {
            $locale = $request->query('locale');
        }

        if (! $locale) {
            $locale = $request->getPreferredLanguage($available) ?: config('app.locale');
        }

        if (! in_array($locale, $available)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
