<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

if (! function_exists('__t')) {
    /**
     * Translate the given key with optional replacements and locale.
     * Fallback to English when translation missing.
     */
    function __t(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        if (Lang::has($key, $locale)) {
            return trans($key, $replace, $locale);
        }
        // fallback to English
        return trans($key, $replace, 'en');
    }
}
