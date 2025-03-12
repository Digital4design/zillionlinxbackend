<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Auth;
use App\Models\Bookmark;
use App\Models\UserBookmark;
use Exception;

class BookmarkController extends Controller
{
    public function addBookmark(Request $request)
    {
        // Validate the request
        $request->validate([
            'url' => 'required|url',
            'title' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:categories,id',
            'addTo' => 'nullable|string',
        ]);

        try {

            $existingBookmark = Bookmark::where('website_url', $request->url)
                ->where('user_id', Auth::id())
                ->first();

            if ($existingBookmark) {
                return response()->json([
                    'error' => 'You have already bookmarked this URL.',
                    'message' => 'Duplicate entry: The bookmark already exists.',
                ], 409);
            }
            $fileName = $request->title . time() . '.png';
            $filePath = storage_path("app/public/{$fileName}");
            Browsershot::url($request->url)
                // ->setOption('executablePath', '/var/www/.cache/puppeteer/chrome/linux-133.0.6943.126/chrome-linux64/chrome')
                ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                ->save($filePath);

            $bookmark = Bookmark::create([
                'title' => $request->title,
                'user_id' => Auth::id(),
                'website_url' => $request->url,
                // 'icon_path' => "storage/{$fileName}",
                'icon_path' => "{$fileName}",
            ]);
            UserBookmark::create([
                'bookmark_id' => $bookmark->id,
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'add_to' => $request->add_to,
            ]);

            return response()->json([
                'message' => 'Bookmark added successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Screenshot failed: ' . $e->getMessage()], 500);
        }
    }

    public function getBookmarks(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:categories,id',
        ]);

        try {
            $userId = Auth::id(); // Get authenticated user ID

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
                        'icon_path'      => asset('storage/' . $userBookmark->bookmark->icon_path ?? null),
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

    public function topLinks(Request $request)
    {
        try {
            $userId = auth::id(); // Get authenticated user ID

            $topLinks = UserBookmark::with('bookmark')
                ->where('user_id', $userId)
                ->where('add_to', 'top_link')
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
                        // 'icon_path'      => $userBookmark->bookmark->icon_path ?? null,
                        'icon_path'      => asset('storage/' . $userBookmark->bookmark->icon_path ?? null),
                    ];
                });

            if ($topLinks->isEmpty()) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'No bookmarks found',
                    'data'    => [],
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Bookmarks retrieved successfully',
                'data'    => $topLinks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeBookmark(Request $request, $id)
    {

        try {
            $topLink = UserBookmark::find($id);

            if (!$topLink) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Top link not found'
                ], 404);
            }
            $topLink->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Top link removed successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function pinBookmark(Request $request, $id)
    {
        try {
            $topLink = UserBookmark::find($id);

            if (!$topLink) {
                return response()->json(['status' => 404, 'message' => 'Top link not found'], 404);
            }

            $topLink->pinned = !$topLink->pinned;
            $topLink->save();

            return response()->json([
                'status' => 200,
                'message' => $topLink->pinned ? 'Top link pinned' : 'Top link unpinned',
                'data' => $topLink
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function reorderBookmark(Request $request)
    {
        try {
            $order = $request->input('order'); // Array of IDs in new order

            if (!$order || !is_array($order)) {
                return response()->json(['status' => 400, 'message' => 'Invalid order data'], 400);
            }

            foreach ($order as $index => $id) {
                UserBookmark::where('id', $id)->update(['position' => $index + 1]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Top links reordered successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
