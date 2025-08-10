<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('landing');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::middleware(['throttle:tasks', 'cost.cap'])->group(function () {
        Route::post('/projects/{project}/tasks/summarize', [TaskController::class, 'summarize'])->name('tasks.summarize');
        Route::post('/projects/{project}/tasks/mindmap', [TaskController::class, 'mindmap'])->name('tasks.mindmap');
        Route::post('/projects/{project}/tasks/slides', [TaskController::class, 'slides'])->name('tasks.slides');
    });
    Route::get('/versions/{version}/download', [TaskController::class, 'download'])->name('versions.download');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/invoice/{invoice}', [BillingController::class, 'invoice'])->name('billing.invoice');
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);
Route::post('/webhooks/paypal', [WebhookController::class, 'paypal']);

require __DIR__.'/admin.php';
