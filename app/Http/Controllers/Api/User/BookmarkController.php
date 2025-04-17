<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Bookmark;
use App\Models\AdminBookmark;
use App\Models\UserBookmark;
use App\Models\Category;
use Exception;

class BookmarkController extends Controller
{

    public function __construct()
    {
        if (Auth::user()->role_id !== 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'status_code' => 403,
            ], 403);
        }
    }


    public function addBookmark(Request $request)
    {
        // Validate the request
        $request->validate([
            'url' => 'required|url',
            'title' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            // 'sub_category_id' => 'nullable|exists:categories,id',
            'addTo' => 'nullable|string',
        ]);

        try {

            $existingBookmark = Bookmark::where('website_url', $request->url)->first();

            // Check if the bookmark already exists dont create a new one
            if ($existingBookmark) {

                //check if new sub category is already exist
                if (isset($request->sub_category_name)) {
                    $cat_data = Category::where('title', 'LIKE', '%' . $request->sub_category_name . '%')->where('user_id', Auth::id())->first();

                    if (empty($cat_data)) {
                        $category =  Category::create([
                            'title' => $request->sub_category_name,
                            'parent_id' => $request->category_id,
                            'user_id' => Auth::id(),
                        ]);
                        $sub_cat_id = $category->id;
                    } else {
                        $cat_data = Category::where('title', 'LIKE', '%' . $request->sub_category_name . '%')->where('user_id', Auth::id())->first();
                        $sub_cat_id = $cat_data->id;
                    }
                } else {
                    $sub_cat_id = $request->sub_category_id ?? null;
                }

                // Check if the bookmark already exists in the user's bookmarks
                $userBookmark = Bookmark::where('website_url', $request->url)
                    ->where('user_id', Auth::id())
                    ->first();

                if ($userBookmark) {
                    return response()->json([
                        'error' => 'You have already bookmarked this URL.',
                        'message' => 'Duplicate entry: The bookmark already exists.',
                    ], 409);
                }

                $bookmark = Bookmark::create([
                    'title' => $request->title,
                    'user_id' => Auth::id(),
                    'website_url' => $existingBookmark->website_url,
                    'icon_path' => $existingBookmark->icon_path,
                ]);
                //dd($sub_cat_id);
                UserBookmark::create([
                    'bookmark_id' => $bookmark->id,
                    'user_id' => Auth::id(),
                    'category_id' => $request->category_id,
                    'sub_category_id' => $sub_cat_id,
                    'add_to' => $request->add_to,
                ]);

                if ($bookmark) {
                    return response()->json([
                        'message' => 'Bookmark added successfully!',
                        'category_id' => $request->category_id,
                        'add_to' => $request->add_to,
                        'sub_category_id' => $sub_cat_id,
                    ]);
                } else {
                    return response()->json([
                        'error' => 'Failed to add bookmark',
                    ], 500);
                }
            } else {
                // create a new bookmark
                if (isset($request->sub_category_name)) {
                    $cat_data = Category::where('title', 'LIKE', '%' . $request->sub_category_name . '%')->where('user_id', Auth::id())->first();

                    if (empty($cat_data)) {
                        $category =  Category::create([
                            'title' => $request->sub_category_name,
                            'parent_id' => $request->category_id,
                            'user_id' => Auth::id(),
                        ]);
                        $sub_cat_id = $category->id;
                    } else {
                        $cat_data = Category::where('title', 'LIKE', '%' . $request->sub_category_name . '%')->where('user_id', Auth::id())->first();
                        $sub_cat_id = $cat_data->id;
                    }
                } else {
                    $sub_cat_id = $request->sub_category_id ?? null;
                }
                $fileName = $request->title . time() . '.png';
                $filePath = storage_path("app/public/{$fileName}");
                $base64Image = Browsershot::url($request->url)
                    ->timeout(60000)
                    ->setOption('userAgent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36')
                    ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                    ->waitUntilFirstPaint()
                    ->setDelay(2000)
                    ->setOption('viewport', ['width' => 1280, 'height' => 720])
                    ->base64Screenshot();

                $imageData = base64_decode($base64Image);
                file_put_contents($filePath, $imageData);


                $bookmark = Bookmark::create([
                    'title' => $request->title,
                    'user_id' => Auth::id(),
                    'website_url' => $request->url,
                    // 'icon_path' => "storage/{$fileName}",
                    'icon_path' => "{$fileName}",
                ]);
                //dd($sub_cat_id);
                UserBookmark::create([
                    'bookmark_id' => $bookmark->id,
                    'user_id' => Auth::id(),
                    'category_id' => $request->category_id,
                    'sub_category_id' => $sub_cat_id,
                    'add_to' => $request->add_to,
                ]);

                if ($bookmark) {
                    return response()->json([
                        'message' => 'Bookmark added successfully!',
                        'category_id' => $request->category_id,
                        'add_to' => $request->add_to,
                        'sub_category_id' => $sub_cat_id,
                    ]);
                } else {
                    return response()->json([
                        'error' => 'Failed to add bookmark',
                    ], 500);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Screenshot failed: ' . $e->getMessage()], 500);
        }
    }


    // public function addBookmark(Request $request)
    // {
    //     // Validate request
    //     $request->validate([
    //         'url' => 'required|url',
    //         'title' => 'required|string',
    //         'category_id' => 'required|exists:categories,id',
    //         'addTo' => 'nullable|string',
    //     ]);

    //     try {
    //         // Check if bookmark already exists
    //         $existingBookmark = Bookmark::where('website_url', $request->url)
    //             ->where('user_id', Auth::id())
    //             ->first();

    //         if ($existingBookmark) {
    //             return response()->json([
    //                 'error' => 'You have already bookmarked this URL.',
    //                 'message' => 'Duplicate entry: The bookmark already exists.',
    //             ], 409);
    //         }

    //         // Extract website logo (og:image, twitter:image, schema.org)
    //         $websiteLogo = null;
    //         try {
    //             $client = new \GuzzleHttp\Client([
    //                 'headers' => [
    //                     'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'
    //                 ]
    //             ]);
    //             $response = $client->get($request->url);
    //             $html = $response->getBody()->getContents();

    //             // Try multiple methods to extract logo
    //             if (preg_match('/<meta property="og:image" content="(.*?)"/i', $html, $matches)) {
    //                 $websiteLogo = $matches[1];
    //             } elseif (preg_match('/<meta name="twitter:image" content="(.*?)"/i', $html, $matches)) {
    //                 $websiteLogo = $matches[1];
    //             } elseif (preg_match('/<meta itemprop="logo" content="(.*?)"/i', $html, $matches)) {
    //                 $websiteLogo = $matches[1];
    //             }
    //         } catch (\Exception $e) {
    //             // Ignore errors and proceed with favicon fallback
    //         }

    //         // Extract domain for favicon as a fallback
    //         $parsedUrl = parse_url($request->url);
    //         $domain = $parsedUrl['host'] ?? '';
    //         $faviconUrl = $domain ? "https://www.google.com/s2/favicons?sz=128&domain={$domain}" : '';

    //         // Use extracted logo if found, otherwise use favicon
    //         $finalLogo = $websiteLogo ?? $faviconUrl;

    //         // Generate a filename for the screenshot
    //         $fileName = $request->title . time() . '.png';
    //         $filePath = storage_path("app/public/{$fileName}");

    //         // Take a screenshot of the website using Browsershot
    //         Browsershot::url($request->url)
    //             ->timeout(120000)
    //             ->setOption('userAgent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36')
    //             ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-http2', '--disable-site-isolation-trials'])
    //             ->save($filePath);

    //         // Save the bookmark with logo and screenshot
    //         $bookmark = Bookmark::create([
    //             'title' => $request->title,
    //             'user_id' => Auth::id(),
    //             'website_url' => $request->url,
    //             'icon_path' => $fileName, // Screenshot file
    //             'favicon_path' => $finalLogo, // Website logo (or favicon fallback)
    //         ]);

    //         UserBookmark::create([
    //             'bookmark_id' => $bookmark->id,
    //             'user_id' => Auth::id(),
    //             'category_id' => $request->category_id,
    //             'sub_category_id' => $request->sub_category_id,
    //             'add_to' => $request->add_to,
    //         ]);

    //         return response()->json([
    //             'message' => 'Bookmark added successfully!',
    //             'category_id' => $request->category_id,
    //             'add_to' => $request->add_to,
    //             'logo_url' => $finalLogo, // Return the website's actual logo
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
    //     }
    // }



    /*
        * Updated: 1-apr-25
        * Fetch Bookmark.
        *
        * This method allows searching Bookmark from database.:
        * - category_id
        * - sub_category_id
        * @param \Illuminate\Http\Request $request
        * @return \Illuminate\Http\JsonResponse
    */
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
                        'title'          => $userBookmark->bookmark->title ?? null,
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
                        'title'          => $userBookmark->bookmark->title ?? null,
                        'icon_path'      => $userBookmark->bookmark->icon_path ?? null,
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
            $Bookmark = Bookmark::find($topLink->bookmark_id);
            if ($Bookmark) {
                if ($Bookmark->image) {
                    $imagePath = "{$Bookmark->icon_path}"; // Adjust the path based on storage
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
                $Bookmark->delete();
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


    /**
     * Date: 26-Mar-2025
     * Add a link to the bookmark.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add_toplinks_bookmark($id)
    {
        try {
            $updated = UserBookmark::where('bookmark_id', $id)->update(['add_to' => 'top_link']);

            if ($updated) {
                return response()->json(['message' => 'Bookmark added to top links successfully'], 200);
            } else {
                return response()->json(['error' => 'Bookmark not found or not updated'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the bookmark', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Date: 26-Mar-2025
     * Remove a link to the bookmark.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove_toplinks_bookmark($id)
    {
        try {
            $updated = UserBookmark::where('bookmark_id', $id)->update(['add_to' => 'bookmark']);

            if ($updated) {
                return response()->json(['message' => 'Bookmark removed from top links successfully'], 200);
            } else {
                return response()->json(['error' => 'Bookmark not found or not updated'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the bookmark', 'details' => $e->getMessage()], 500);
        }
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
    public function ImportBookmark(Request $request)
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
}
