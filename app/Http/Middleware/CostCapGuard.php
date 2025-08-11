<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Symfony\Component\HttpFoundation\Response;

class CostCapGuard {
    public function handle(Request $request, Closure $next): Response {
        $tenant = $request->user()?->tenant; if (!$tenant) abort(403);
        $monthStart = now()->startOfMonth();
        $spent = DB::table('usage_logs')->where('tenant_id',$tenant->id)->where('created_at','>=',$monthStart)->sum('cost_cents');
        if ($spent >= $tenant->monthly_cost_cap_cents) {
            return response()->json(['message'=>__('ui.cost_cap_reached')], 422);
        }
        return $next($request);
    }
}