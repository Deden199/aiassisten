<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installedFlag = base_path('storage/installed');

        $isInstalled = file_exists($installedFlag) || config('app.installed', false);

        // Allow installer routes to be accessed when not installed
        if (! $isInstalled) {
            // Permit only install routes and assets
            if ($request->is('install*') || $request->is('storage/*') || $request->is('public/*')) {
                return $next($request);
            }
            return redirect()->route('install.welcome');
        }

        // If installed, block /install routes
        if ($isInstalled && $request->is('install*')) {
            return redirect('/');
        }

        return $next($request);
    }
}
