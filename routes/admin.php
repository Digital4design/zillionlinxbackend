<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\BookmarkController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\DashboardController;
use Illuminate\Http\Request;

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
    Route::post('/delete/categories', [CategoryController::class, 'destroy']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);
    Route::post('/user/update/{id}', [UserController::class, 'update']);
    Route::get('/getAllBookmarks', [BookmarkController::class, 'getAllBookmarks']);
    Route::post('/dashboard', [DashboardController::class, 'index']);
    Route::post('/six-months-user', [DashboardController::class, 'sixMonthsUser']);
    Route::post('/six-months-bookmark', [DashboardController::class, 'sixMonthsBookmarks']);
    Route::post('/delete-Bookmarks', [BookmarkController::class, 'destroy']);
    Route::post('/categories/reorder', [CategoryController::class, 'reorderCategory']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/import-bookmark', [BookmarkController::class, 'import']);
    Route::get('/listing-admin-bookmark', [BookmarkController::class, 'adminImportBookmark']);
    Route::post('/delete-admin-bookmark', [BookmarkController::class, 'deleteImportBookmark']);
    Route::post('/main/categories', [CategoryController::class, 'Category']);
    Route::post('/sub/categories', [CategoryController::class, 'subCategory']);
    Route::get('/instant-linx-category', [CategoryController::class, 'instantLinxCategories']);
});
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out successfully']);
});
