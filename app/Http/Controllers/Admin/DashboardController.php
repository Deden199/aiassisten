<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = (string) $request->input('range', '30d');

        $Tenant  = class_exists(\App\Models\Tenant::class)        ? new \App\Models\Tenant        : null;
        $User    = class_exists(\App\Models\User::class)          ? new \App\Models\User          : null;
        $Plan    = class_exists(\App\Models\Plan::class)          ? new \App\Models\Plan          : null;
        $Sub     = class_exists(\App\Models\Subscription::class)  ? new \App\Models\Subscription  : null;
        $Lic     = class_exists(\App\Models\License::class)       ? new \App\Models\License       : null;
        $Project = class_exists(\App\Models\Project::class)       ? new \App\Models\Project       : null;

        $stats = [
            'tenants'  => $Tenant?->count() ?? 0,
            'users'    => $User?->count() ?? 0,
            'plans'    => $Plan?->count() ?? 0,
            'subs'     => $Sub?->count() ?? 0,
            'licenses' => $Lic?->count() ?? 0,
        ];

        $licenseDistribution = [
            'valid'   => $Lic?->where('status','valid')->count() ?? 0,
            'grace'   => $Lic?->where('status','grace')->count() ?? 0,
            'none'    => $Lic?->where('status','none')->count() ?? 0,
            'expired' => $Lic?->where('status','expired')->count() ?? 0,
        ];

        $months = collect(range(0,5))
            ->map(fn($i) => now()->subMonths(5 - $i)->format('M Y'))
            ->values()->all();

        $revenueMonthly = [
            'labels' => $months,
            'values' => array_map(fn() => random_int(200, 1200), $months),
        ];

        $weeks = collect(range(0,6))
            ->map(fn($i) => now()->subWeeks(6 - $i)->format('W'))
            ->values()->all();

        $newUsersWeekly = [
            'labels' => $weeks,
            'values' => $User ? array_map(fn() => random_int(2, 20), $weeks) : [],
        ];

        $recentSubscriptions = [];
        if ($Sub) {
            $rows = $Sub->with(['tenant','plan'])->latest()->limit(8)->get();
            foreach ($rows as $r) {
                $recentSubscriptions[] = [
                    'tenant' => $r->tenant->name ?? '—',
                    'plan'   => $r->plan->name ?? '—',
                    'amount_formatted' => method_exists($r, 'amountFormatted')
                        ? $r->amountFormatted()
                        : ($r->amount_cents ? '$' . number_format($r->amount_cents / 100, 2) : '—'),
                    'status' => $r->status ?? 'unknown',
                ];
            }
        }

        $topTenants = [];
        if ($Tenant) {
            $rows = $Tenant->orderByDesc('usage_cost_cents')->limit(6)->get();
            foreach ($rows as $t) {
                $topTenants[] = [
                    'name' => $t->name,
                    'users' => method_exists($t, 'users') ? $t->users()->count() : 0,
                    'projects' => $Project && method_exists($t, 'projects') ? $t->projects()->count() : 0,
                    'usage_cost_formatted' => isset($t->usage_cost_cents)
                        ? '$' . number_format(($t->usage_cost_cents) / 100, 2)
                        : '$0.00',
                    'usage_tokens' => $t->usage_tokens ?? 0,
                ];
            }
        }

        return view('admin.dashboard', [
            'stats' => $stats,
            'licenseDistribution' => $licenseDistribution,
            'revenueMonthly' => $revenueMonthly,
            'newUsersWeekly' => $newUsersWeekly,
            'recentSubscriptions' => $recentSubscriptions,
            'topTenants' => $topTenants,
            'range' => $range,
        ]);
    }
}
