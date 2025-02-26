<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\CategoryController;

// Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Route::get('/categories', [CategoryController::class, 'index']);
    // Route::post('/categories', [CategoryController::class, 'store']);
    // Route::get('/categories/{id}', [CategoryController::class, 'show']);
    // Route::put('/categories/{id}', [CategoryController::class, 'update']);
    // Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
// });
Route::prefix('admin')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});