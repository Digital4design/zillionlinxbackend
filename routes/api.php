<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\User\BookmarkController;
use App\Http\Controllers\Api\User\CategoryController;
use App\Http\Controllers\Api\User\SearchController;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailNotify;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

Route::post('/clear-cache', function () {
    try {
        // Execute cache clearing commands
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully!'
        ], 200);
    } catch (\Exception $ex) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to clear cache: ' . $ex->getMessage()
        ], 500);
    }
});

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/send-test-email', function () {
    try {
        Mail::to('kartik.d4d@gmail.com')->send(new MailNotify());
        return response()->json(['message' => 'Email sent successfully.']);
    } catch (\Exception $e) {
        Log::error('Mail Error: ' . $e->getMessage());
        return response()->json(['message' => 'Mail sending failed.', 'error' => $e->getMessage()], 500);
    }
});
Route::get('/bookmarks-test', [BookmarkController::class, 'test']);
Route::middleware('auth:sanctum')->group(function () {

    // Bookmark routes
    Route::post('/add-bookmark', [BookmarkController::class, 'addBookmark']);
    Route::get('/bookmarks', [BookmarkController::class, 'getBookmarks']);
    Route::get('/top-links', [BookmarkController::class, 'topLinks']);

    Route::delete('/bookmark/{id}', [BookmarkController::class, 'removeBookmark']);
    Route::post('/bookmark/{id}/pin', [BookmarkController::class, 'pinBookmark']);
    Route::post('/bookmark/reorder', [BookmarkController::class, 'reorderBookmark']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);

    Route::post('/search', [SearchController::class, 'search']);

    // Test Email Route (only authenticated users)

});

Route::middleware('auth:sanctum')->get('/check-token', function (Request $request) {
    return response()->json([
        'message' => 'Token is valid',
        'user' => $request->user(),
    ], 200);
});

require __DIR__ . '/admin.php';
