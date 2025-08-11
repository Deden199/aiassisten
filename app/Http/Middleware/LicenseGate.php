<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;

class LicenseGate {
    public function handle(Request $request, Closure $next): Response {
        $t = $request->user()?->tenant; if (!$t) return abort(403);
        $status = optional($t->license)->status ?? 'valid';
        if ($status !== 'valid' && $status !== 'grace') abort(402, __('ui.license_required'));
        return $next($request);
    }
}