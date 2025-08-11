<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AiTask; // ⬅️ perlu buat fallback flat

// Landing page
Route::get('/', fn () => view('welcome'))->name('landing');

// Locale change
Route::post('/locale', [LocaleController::class, 'update'])->name('locale');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');

    // Projects
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Tasks (throttled + cost cap)
    Route::middleware(['throttle:tasks', 'cost.cap'])->group(function () {
        Route::post('/projects/{project}/tasks/summarize', [TaskController::class, 'summarize'])->name('tasks.summarize');
        Route::match(['get', 'post'], '/projects/{project}/tasks/mindmap', [TaskController::class, 'mindmap'])->name('tasks.mindmap');
        Route::post('/projects/{project}/tasks/slides', [TaskController::class, 'slides'])->name('tasks.slides');
    });

    // ✅ Polling status (spesifik, pakai scoped binding + UUID)
    Route::get('/projects/{project}/tasks/{task}', [TaskController::class, 'show'])
        ->scopeBindings()
        ->whereUuid(['project','task'])
        ->name('tasks.show');

    // ✅ Fallback flat: /tasks/{task} → redirect ke nested yang benar
    Route::get('/tasks/{task}', function (AiTask $task) {
        return redirect()->route('tasks.show', [$task->project_id, $task->id]);
    })->whereUuid('task')->name('tasks.show.flat');

    // (opsional) Friendly GET fallback untuk action manual
    Route::get('/projects/{project}/tasks/{type}', fn () => redirect()->route('dashboard'))
        ->whereIn('type', ['summarize','mindmap','slides'])
        ->name('tasks.fallback');

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
