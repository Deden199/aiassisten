<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:ai')->post('/upload', [UploadController::class, 'store']);
