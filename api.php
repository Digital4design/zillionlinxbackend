<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\User\BookmarkController;
use App\Http\Controllers\Api\User\CategoryController;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailNotify;


Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {

    // Bookmark routes
    Route::post('/add-bookmark', [BookmarkController::class, 'addBookmark']);
    Route::get('/bookmarks', [BookmarkController::class, 'getBookmarks']); 
    Route::get('/top-links', [BookmarkController::class, 'topLinks']);
   
    Route::delete('/top-links/{id}', [BookmarkController::class, 'removeTopLink']);
    Route::post('/top-links/{id}/pin', [BookmarkController::class, 'pinTopLink']);
    Route::post('/bookmarks/reorder', [BookmarkController::class, 'reorderTopLinks']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
   


    // Test Email Route (only authenticated users)
    Route::get('/send-test-email', function () {
        Mail::to('kartik.d4d@gmail.com')->send(new MailNotify());
        return response()->json(['message' => 'Email sent successfully.']);
    });
});

require __DIR__ . '/admin.php';
