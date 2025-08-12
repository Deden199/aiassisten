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
        // Tambahkan middleware kustom ke grup web
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ApplyRTL::class,
            \App\Http\Middleware\EnsureInstalled::class,
        ]);

        // Alias untuk dipakai di routes
        $middleware->alias([
            'admin'          => \App\Http\Middleware\EnsureAdmin::class,
            'enforce.tenant' => \App\Http\Middleware\EnforceTenant::class,
            'license.gate'   => \App\Http\Middleware\LicenseGate::class,
            'cost.cap'       => \App\Http\Middleware\CostCapGuard::class,
            'installed'      => \App\Http\Middleware\EnsureInstalled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
