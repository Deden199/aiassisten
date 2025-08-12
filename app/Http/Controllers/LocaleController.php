<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $available = config('app.available_locales', []);
        $locale = $request->input('locale');

        if (! in_array($locale, $available)) {
            $locale = config('app.locale');
        }

        if ($user = Auth::user()) {
            $user->locale = $locale;
            $user->save();
        }

        Cookie::queue('locale', $locale, 60 * 24 * 365);

        return back();
    }
}

