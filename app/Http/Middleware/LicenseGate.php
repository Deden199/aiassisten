<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseGate
{
    public function handle(Request $request, Closure $next): Response
    {
        // allow bypass in dev/CI
        if (config('license.bypass')) {
            return $next($request);
        }

        $tenant = $request->user()?->tenant;

        if (! $tenant) {
            abort(403);
        }

        $license = $tenant->license;

        // allow grace
        if ($license) {
            if ($license->status === 'valid') {
                return $next($request);
            }
            if ($license->status === 'grace' && $license->grace_until && now()->lt($license->grace_until)) {
                return $next($request);
            }
        }

        // block premium features
        abort(402, __('ui.license_required'));
    }
}
