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

    // public function index(Request $request)
    // {
    //     $validated = $request->validate([
    //         'category_id' => 'required|exists:categories,id',
    //         'sub_category_id' => 'nullable|exists:categories,id',
    //         'user_id' => 'required'
    //     ]);

    //     try {
    //         $userId = $validated['user_id']; // Get authenticated user ID

    //         $bookmarks = UserBookmark::with('bookmark')
    //             ->where('user_id', $userId)
    //             ->where('category_id', $validated['category_id'])
    //             ->when(!empty($validated['sub_category_id']), function ($query) use ($validated) {
    //                 $query->where('sub_category_id', $validated['sub_category_id']);
    //             })
    //             ->orderByDesc('pinned') // Show pinned bookmarks first
    //             ->orderBy('position', 'asc') // Then order by position
    //             ->get()
    //             ->map(function ($userBookmark) {
    //                 return [
    //                     'id'             => $userBookmark->id,
    //                     'bookmark_id'    => $userBookmark->bookmark_id,
    //                     'user_id'        => $userBookmark->user_id,
    //                     'category_id'    => $userBookmark->category_id,
    //                     'sub_category_id' => $userBookmark->sub_category_id,
    //                     'add_to'         => $userBookmark->add_to,
    //                     'pinned'         => $userBookmark->pinned,
    //                     'position'       => $userBookmark->position,
    //                     'created_at'     => $userBookmark->created_at,
    //                     'updated_at'     => $userBookmark->updated_at,
    //                     'website_url'    => $userBookmark->bookmark->website_url ?? null,
    //                     'icon_path'      => $userBookmark->bookmark->icon_path ?? null,
    //                 ];
    //             });

    //         return response()->json([
    //             'message'   => $bookmarks->isNotEmpty() ? 'Bookmarks retrieved successfully!' : 'No bookmarks found.',
    //             'bookmarks' => $bookmarks,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Failed to fetch bookmarks: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Date: 20-Mar-2025
     * Get all bookmarks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllBookmarks(Request $request)
    {
        try {
            $search = $request->input('search');
            $categoryId = $request->input('category_id');
            $subCategoryId = $request->input('sub_category_id');
            $pinned = $request->input('pinned');

            $query = UserBookmark::with('bookmark', 'user')
                ->when($search, function ($q) use ($search) {
                    return $q->whereHas('bookmark', function ($query) use ($search) {
                        $query->where('website_url', 'LIKE', "%$search%");
                    });
                })
                ->when($categoryId, function ($q) use ($categoryId) {
                    return $q->where('category_id', $categoryId);
                })
                ->when($subCategoryId, function ($q) use ($subCategoryId) {
                    return $q->where('sub_category_id', $subCategoryId);
                })
                ->when(isset($pinned), function ($q) use ($pinned) {
                    return $q->where('pinned', $pinned);
                })
                ->orderByDesc('pinned')
                ->orderBy('position', 'asc')
                ->paginate(10); // Ensure consistent pagination

            $formattedBookmarks = $query->map(function ($userBookmark) {
                return [
                    'id'               => $userBookmark->id,
                    'bookmark_id'      => $userBookmark->bookmark_id,
                    'user_id'          => $userBookmark->user_id,
                    'user_name'        => ($userBookmark->user->first_name ?? '') . ' ' . ($userBookmark->user->last_name ?? ''),
                    'category_id'      => $userBookmark->category_id,
                    'category_name'    => $userBookmark->category_name->title ?? null,
                    'sub_category_id'  => $userBookmark->sub_category_id,
                    'sub_category_name' => $userBookmark->sub_category_name->title ?? null,
                    'add_to'           => $userBookmark->add_to,
                    'pinned'           => $userBookmark->pinned,
                    'title'           =>  $userBookmark->bookmark->title ?? null,
                    'position'         => $userBookmark->position,
                    'created_at'       => $userBookmark->created_at,
                    'updated_at'       => $userBookmark->updated_at,
                    'website_url'      => $userBookmark->bookmark->website_url ?? null,
                    'icon_path'        => $userBookmark->bookmark->icon_path ?? null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'current_page'   => $query->currentPage(),
                    'data'           => $formattedBookmarks,
                    'first_page_url' => $query->url(1),
                    'from'           => $query->firstItem(),
                    'last_page'      => $query->lastPage(),
                    'last_page_url'  => $query->url($query->lastPage()),
                    'links'          => [
                        [
                            'url'    => $query->previousPageUrl(),
                            'label'  => '&laquo; Previous',
                            'active' => $query->onFirstPage() ? false : true
                        ],
                        [
                            'url'    => $query->url($query->currentPage()),
                            'label'  => (string) $query->currentPage(),
                            'active' => true
                        ],
                        [
                            'url'    => $query->nextPageUrl(),
                            'label'  => 'Next &raquo;',
                            'active' => $query->hasMorePages() ? true : false
                        ]
                    ],
                    'next_page_url' => $query->nextPageUrl(),
                    'path'          => $request->url(),
                    'per_page'      => $query->perPage(),
                    'prev_page_url' => $query->previousPageUrl(),
                    'to'            => $query->lastItem(),
                    'total'         => $query->total()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bookmarks: ' . $e->getMessage(),
            ], 500);
        }
    }
}
