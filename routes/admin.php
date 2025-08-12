<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Admin\SlideTemplateController;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('tenants', TenantController::class)->only(['index']);
        Route::resource('users', UserController::class)->only(['index','edit','update']);
        Route::resource('plans', PlanController::class)->only(['index']);
        Route::resource('subscriptions', SubscriptionController::class)->only(['index','edit','update']);

        Route::resource('licenses', LicenseController::class)
            ->only(['index','update'])
            ->middleware('throttle:5,1');
        Route::post('licenses/{license}/deactivate', [LicenseController::class, 'deactivate'])
            ->name('licenses.deactivate');

        // Opsional: pastikan parameter binding konsisten dengan {slide_template}
        Route::resource('slide-templates', SlideTemplateController::class)
            ->parameters(['slide-templates' => 'slide_template']);

        Route::post('slide-templates/{slide_template}/duplicate', [SlideTemplateController::class, 'duplicate'])
            ->name('slide-templates.duplicate');
    });

require __DIR__.'/auth.php';
