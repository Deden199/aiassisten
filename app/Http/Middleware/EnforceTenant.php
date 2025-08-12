<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;

class EnforceTenant {
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();
        if (!$user || !$user->tenant || !$user->tenant->is_active) {
            abort(403, __('ui.tenant_inactive'));
        }
        // Optionally: set tenant context (e.g., app()->instance('tenant', $user->tenant))
        return $next($request);
    }
}