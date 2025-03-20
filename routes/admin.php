<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\UserController;

use App\Http\Controllers\Api\AuthController;

Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Admin Login
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user', [UserController::class, 'create']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);
    Route::post('/user/update/{id}', [UserController::class, 'update']);
});
