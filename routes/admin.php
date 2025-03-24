<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\BookmarkController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\DashboardController;

Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Admin Login
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user', [UserController::class, 'create']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/update-categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);
    Route::post('/user/update/{id}', [UserController::class, 'update']);
    Route::get('/getAllBookmarks', [BookmarkController::class, 'getAllBookmarks']);
    Route::post('/dashboard', [DashboardController::class, 'index']);
});
