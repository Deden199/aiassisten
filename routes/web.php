<?php

use Illuminate\Support\Facades\Route;

Route::prefix('{locale?}')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
});
