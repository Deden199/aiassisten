<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/healthz',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ikutkan middleware ini di grup web
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ResolveLocale::class,
            \App\Http\Middleware\ApplyRTL::class,
        ]);

        // DAFTARKAN ALIAS YANG DIPAKAI DI ROUTES
        $middleware->alias([
            'admin'          => \App\Http\Middleware\EnsureAdmin::class,
            'enforce.tenant' => \App\Http\Middleware\EnforceTenant::class,
            'license.gate'   => \App\Http\Middleware\LicenseGate::class,
            'cost.cap'       => \App\Http\Middleware\CostCapGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
