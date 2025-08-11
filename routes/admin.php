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
Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->only(['index','edit','update']);
        Route::resource('plans', PlanController::class)->only(['index']);
Route::resource('subscriptions', \App\Http\Controllers\Admin\SubscriptionController::class)->only(['index','edit','update']);
        Route::resource('licenses', LicenseController::class)->only(['index', 'update']);
        Route::post('slide-templates/{slide_template}/duplicate', [SlideTemplateController::class, 'duplicate'])->name('slide-templates.duplicate');
        Route::resource('slide-templates', SlideTemplateController::class);
    });
require __DIR__.'/auth.php';
