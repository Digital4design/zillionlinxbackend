<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserBookmark;
use App\Models\Bookmark;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class BookmarkController extends Controller
{
    public function __construct()
    {
        if (Auth::check() && Auth::user()->role_id !== 1) {
            abort(response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'status_code' => 403,
            ], 403));
        }
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:categories,id',
            'user_id' => 'required'
        ]);

        try {
            $userId = $validated['user_id']; // Get authenticated user ID

            $bookmarks = UserBookmark::with('bookmark')
                ->where('user_id', $userId)
                ->where('category_id', $validated['category_id'])
                ->when(!empty($validated['sub_category_id']), function ($query) use ($validated) {
                    $query->where('sub_category_id', $validated['sub_category_id']);
                })
                ->orderByDesc('pinned') // Show pinned bookmarks first
                ->orderBy('position', 'asc') // Then order by position
                ->get()
                ->map(function ($userBookmark) {
                    return [
                        'id'             => $userBookmark->id,
                        'bookmark_id'    => $userBookmark->bookmark_id,
                        'user_id'        => $userBookmark->user_id,
                        'category_id'    => $userBookmark->category_id,
                        'sub_category_id' => $userBookmark->sub_category_id,
                        'add_to'         => $userBookmark->add_to,
                        'pinned'         => $userBookmark->pinned,
                        'position'       => $userBookmark->position,
                        'created_at'     => $userBookmark->created_at,
                        'updated_at'     => $userBookmark->updated_at,
                        'website_url'    => $userBookmark->bookmark->website_url ?? null,
                        'icon_path'      => $userBookmark->bookmark->icon_path ?? null,
                    ];
                });

            return response()->json([
                'message'   => $bookmarks->isNotEmpty() ? 'Bookmarks retrieved successfully!' : 'No bookmarks found.',
                'bookmarks' => $bookmarks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch bookmarks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Date: 20-Mar-2025
     * Get all bookmarks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllBookmarks()
    {
        try {
            $bookmarks = UserBookmark::with('bookmark', 'user')
                ->orderByDesc('pinned') // Show pinned bookmarks first
                ->orderBy('position', 'asc') // Then order by position
                ->get()
                ->map(function ($userBookmark) {
                    return [
                        'id'             => $userBookmark->id,
                        'bookmark_id'    => $userBookmark->bookmark_id,
                        'user_id'        => $userBookmark->user_id,
                        'user_name'      => ($userBookmark->user->first_name ?? '') . ' ' . ($userBookmark->user->last_name ?? ''), // Fetch user name
                        'category_id'    => $userBookmark->category_id,
                        'category_name'  => $userBookmark->category_name->title ?? null, // Fetch category name
                        'sub_category_id' => $userBookmark->sub_category_id,
                        'sub_category_name' => $userBookmark->sub_category_name->title ?? null, // Fetch sub-category name
                        'add_to'         => $userBookmark->add_to,
                        'pinned'         => $userBookmark->pinned,
                        'position'       => $userBookmark->position,
                        'created_at'     => $userBookmark->created_at,
                        'updated_at'     => $userBookmark->updated_at,
                        'website_url'    => $userBookmark->bookmark->website_url ?? null,
                        'icon_path'      => $userBookmark->bookmark->icon_path ?? null,
                    ];
                });

            return response()->json([
                'message'   => $bookmarks->isNotEmpty() ? 'All bookmarks retrieved successfully!' : 'No bookmarks found.',
                'bookmarks' => $bookmarks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch bookmarks: ' . $e->getMessage(),
            ], 500);
        }
    }
}
