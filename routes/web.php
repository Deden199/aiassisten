<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AiTask;

// Landing page
Route::get('/', fn () => view('welcome'))->name('landing');

// Locale change
Route::post('/locale', [LocaleController::class, 'update'])->name('locale');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard (pisah dari Projects)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Tasks (throttled + cost cap + quota)
    Route::middleware(['throttle:tasks', 'cost.cap', \App\Http\Middleware\EnsureActiveSubscriptionAndQuota::class])->group(function () {
        Route::post('/projects/{project}/tasks/summarize', [TaskController::class, 'summarize'])->name('tasks.summarize');
        Route::match(['get', 'post'], '/projects/{project}/tasks/mindmap', [TaskController::class, 'mindmap'])->name('tasks.mindmap');
        Route::post('/projects/{project}/tasks/slides', [TaskController::class, 'slides'])->name('tasks.slides');
    });

    // Polling status (scoped binding + UUID untuk keduanya)
    Route::get('/projects/{project}/tasks/{task}', [TaskController::class, 'show'])
        ->scopeBindings()
        ->whereUuid(['project','task'])
        ->name('tasks.show');

    // Fallback flat: /tasks/{task} → redirect ke nested yang benar (pakai model)
    Route::get('/tasks/{task}', function (AiTask $task) {
        return redirect()->route('tasks.show', [$task->project, $task]);
    })->whereUuid('task')->name('tasks.show.flat');

    // Friendly GET fallback untuk action manual (opsional)
    Route::get('/projects/{project}/tasks/{type}', fn () => redirect()->route('dashboard'))
        ->whereIn('type', ['summarize','mindmap','slides'])
        ->name('tasks.fallback');

    // Versions
    Route::get('/versions/{version}/preview', [TaskController::class, 'preview'])->name('versions.preview');
    Route::get('/versions/{version}/download', [TaskController::class, 'download'])->name('versions.download');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
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
