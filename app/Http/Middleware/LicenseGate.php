<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant) {
            abort(403);
        }

        $license = $tenant->license;

        if (! $license || ! in_array($license->status, ['valid', 'grace'], true)) {
            abort(402, __('ui.license_required'));
        }

        return $next($request);
    }
}
