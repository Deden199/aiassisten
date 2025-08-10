<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Landing page
Route::get('/', fn () => view('welcome'))->name('landing');

// Locale change
Route::post('/locale', [LocaleController::class, 'update'])->name('locale');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard - tambahkan fallback guard
    Route::get('/dashboard', function () {
        try {
            return app(ProjectController::class)->index();
        } catch (\Throwable $e) {
            \Log::error('Dashboard error', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);
            // Fallback view supaya nggak blank/500
            return view('dashboard-fallback', [
                'message' => 'Terjadi kesalahan saat memuat dashboard.',
            ]);
        }
    })->name('dashboard');

    // Projects
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Tasks (throttled + cost cap)
    Route::middleware(['throttle:tasks', 'cost.cap'])->group(function () {
        Route::post('/projects/{project}/tasks/summarize', [TaskController::class, 'summarize'])->name('tasks.summarize');
        Route::post('/projects/{project}/tasks/mindmap', [TaskController::class, 'mindmap'])->name('tasks.mindmap');
        Route::post('/projects/{project}/tasks/slides', [TaskController::class, 'slides'])->name('tasks.slides');
    });

    // Versions
    Route::get('/versions/{version}/download', [TaskController::class, 'download'])->name('versions.download');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/invoice/{invoice}', [BillingController::class, 'invoice'])->name('billing.invoice');

    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

// Webhooks
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);
Route::post('/webhooks/paypal', [WebhookController::class, 'paypal']);

// Admin routes
require __DIR__.'/admin.php';
