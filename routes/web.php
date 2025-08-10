<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ProjectController, TaskController};

Route::get('/', fn () => view('welcome'))->name('landing');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::post('/projects/{project}/tasks/summarize', [TaskController::class, 'summarize'])->name('tasks.summarize');
    Route::post('/projects/{project}/tasks/mindmap',   [TaskController::class, 'mindmap'])->name('tasks.mindmap');
    Route::post('/projects/{project}/tasks/slides',    [TaskController::class, 'slides'])->name('tasks.slides');
    Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');
});
