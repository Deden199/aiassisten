<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function(){ return view('landing'); })->name('landing');

Route::middleware(['auth','enforce.tenant','license.gate'])->group(function(){
    Route::get('/app', fn() => view('app.dashboard'))->name('app.dashboard');
    Route::view('/app/projects', 'app.projects.index')->name('app.projects');
});

// Admin placeholder
Route::middleware(['auth'])->group(function(){
    Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
});