<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ApplyRTL
{
    public function handle(Request $request, Closure $next)
    {
        $isRtl = in_array(app()->getLocale(), ['ar']);
        View::share('isRtl', $isRtl);
        return $next($request);
    }
}
