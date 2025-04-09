<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserBookmark;
use App\Models\Bookmark;
use App\Models\AdminBookmark;
use Illuminate\Support\Facades\Auth;

use Exception;

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
                        $query->where('title', 'LIKE', "%$search%");
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
                ->orderByDesc('created_at')
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

    /**
     * Date: 27-Mar-2025
     * Delete Bookmark by ID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $ids = $request->input('ids'); // Expecting an array of bookmark IDs

            if (empty($ids)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No IDs provided'
                ], 400);
            }

            // Find all user bookmarks with the provided IDs
            $topLinks = Bookmark::whereIn('id', $ids)->get();

            if ($topLinks->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No bookmarks found for the given IDs'
                ], 404);
            }


            foreach ($topLinks as $key => $topLink) {
                // dd($topLink);
                // Delete associated Bookmark if it exists
                $Bookmark = Bookmark::find($topLink->id);

                if ($Bookmark) {

                    if ($Bookmark->icon_path) {
                        //  $imagePath = "{$Bookmark->icon_path}"; // Adjust the path based on storage
                        if (Storage::disk('public')->exists($Bookmark->icon_path)) {
                            Storage::disk('public')->delete($Bookmark->icon_path);
                        }
                    }
                    $Bookmark->delete();
                }

                // Delete the user bookmark
                $topLink->delete();
            }

            return response()->json([
                'status' => 200,
                'message' => 'Bookmarks removed successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Date: 8-Apr-2025
     * Import bookmarks from an uploaded file (HTML or JSON).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|mimes:html,json|max:2048',
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getPathname());

        if ($file->getClientOriginalExtension() == 'html') {
            $bookmarks = $this->parseHtml($content);
        } else {
            $bookmarks = json_decode($content, true) ?? [];
        }


        foreach ($bookmarks as $bookmark) {
            $existingBookmark = AdminBookmark::where('website_url', $bookmark['url'])->first();

            if (!$existingBookmark) {
                $bookmarkCreated = AdminBookmark::create([
                    'user_id' => Auth::id(),
                    'title' => $bookmark['title'],
                    'website_url' => $bookmark['url'],
                ]);
            }
        }

        if (!empty($bookmarkCreated)) {
            return response()->json(['message' => 'Bookmarks imported successfully'], 200);
        } else {
            return response()->json(['message' => 'Duplicate bookmarks found'], 400);
        }
    }

    /**
     * Date: 20-Mar-2025
     * Parse bookmarks from an HTML file (exported from a browser).
     *
     * @param string $htmlContent
     * @return array
     */
    private function parseHtml($htmlContent)
    {
        $bookmarks = [];
        preg_match_all('/<A HREF="([^"]+)".*>(.*?)<\/A>/', $htmlContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $bookmarks[] = ['url' => $match[1], 'title' => strip_tags($match[2])];
        }

        return $bookmarks;
    }

    /**
     * Date: 8-Apr-25
     * Function: adminImportBookmark
     *
     * Description:
     * Retrieves a list of admin bookmarks, selecting only the ID, title, 
     * and website URL. The bookmarks are ordered by their creation date 
     * in descending order (most recent first).
     *
     * @return \Illuminate\Support\Collection
     */
    public function adminImportBookmark(Request $request)
    {
        $BookmarkData = AdminBookmark::select('id', 'title', 'website_url')
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->input('search') . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);



        if ($BookmarkData->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No bookmarks found',
                'data' => []
            ], 404);
        }
        return response()->json([
            'status' => 200,
            'message' => 'Bookmark retrieved successfully',
            'data' => $BookmarkData
        ], 200);
    }

    /**
     * Date: 8-Apr-25
     * Delete multiple ImportBookmark entries.
     *
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function deleteImportBookmark(Request $request)
    {
        // var_dump($request->ids());
        // Validate the request to ensure 'ids' is an array of integers
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:admin_bookmarks,id',
        ]);

        // Perform bulk deletion
        $deleted = AdminBookmark::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => 'Selected bookmarks deleted successfully.',
            'deleted_count' => $deleted,
        ]);
    }
}
