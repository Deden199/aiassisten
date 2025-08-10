<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('ai', function (Request $request) {
            $userId = optional($request->user())->id;

            return Limit::perMinute(60)->by($request->ip().'|'.$userId);
        });

        RateLimiter::for('tasks', function (Request $request) {
            $userId = optional($request->user())->id ?? $request->ip();

            return Limit::perDay(50)
                ->by($userId)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => __('ui.tasks_daily_limit_reached'),
                    ], 429, $headers);
                });
        });
    }
}
