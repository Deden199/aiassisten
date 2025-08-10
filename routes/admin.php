<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\LicenseController;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('tenants', TenantController::class)->only(['index']);
        Route::resource('users', UserController::class)->only(['index']);
        Route::resource('plans', PlanController::class)->only(['index']);
        Route::resource('subscriptions', SubscriptionController::class)->only(['index']);
        Route::resource('licenses', LicenseController::class)->only(['index', 'update']);
    });
