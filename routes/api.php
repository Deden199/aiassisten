<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth:sanctum','enforce.tenant','license.gate','cost.cap'])->group(function(){
    Route::post('/projects', [\App\Http\Controllers\ProjectController::class,'store']);
    Route::post('/projects/{project}/tasks/summarize', [\App\Http\Controllers\TaskController::class,'summarize']);
});