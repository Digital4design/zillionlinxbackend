<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bookmark;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;


class SearchController extends Controller
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


    /*
    * Date: 11-mar-25
    * Last Updated: 27-mar-25
    * Search for bookmarks based on title.
    *
    * This method allows searching bookmarks based on the following parameters:
    * - title
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function search(Request $request)
    {
        $query = Bookmark::query();

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $bookmarks = $query->select('website_url', 'icon_path', 'title')->get();

        //call functions
        $googleResults = [];
        $wikimediaResults = [];
        $ebayResults = [];
        $youtubeResults = [];
        $amazonResults = [];

        if ($request->has('title')) {
            $googleResults = $this->searchGoogle($request->search);
            $ebayResults = $this->searchEbay($request->search);
            $youtubeResults = $this->searchYouTube($request->search);
            $amazonStaticLink = $this->searchAmazon($request->search);
            $youtubeStaticLink = "https://www.youtube.com/results?search_query=" . urlencode($request->search);
            $ebayStaticLink = "https://www.ebay.com/sch/i.html?_nkw=" . urlencode($request->search);
            $walmartStaticLink = "https://www.walmart.com/search/?query=" . urlencode($request->search);
            $aliexpressStaticLink = "https://www.aliexpress.com/wholesale?SearchText=" . urlencode($request->search);
            $etsyStaticLink = "https://www.etsy.com/search?q=" . urlencode($request->search);
            $neweggStaticLink = "https://www.newegg.com/p/pl?d=" . urlencode($request->search);
            $googleImagesURL = "https://www.google.com/search?tbm=isch&q=" . urlencode($request->search);
        }

        // Return a search results
        return response()->json([
            'success' => true,
            'data' => [
                'bookmarks' => $bookmarks,
                'google_search_results' => $googleResults,
                'ebay_search_results' => $ebayResults,
                'youtube_search_results' => $youtubeResults,
                'amazonStaticLink' => $amazonStaticLink,
                'youtubeStaticLink' => $youtubeStaticLink,
                'ebayStaticLink' => $ebayStaticLink,
                'walmartStaticLink' => $walmartStaticLink,
                'aliexpressStaticLink' => $aliexpressStaticLink,
                'etsyStaticLink' => $etsyStaticLink,
                'neweggStaticLink' => $neweggStaticLink,
                'googleImagesURL' => $googleImagesURL,
            ],
        ]);
    }

    /*
    * Date: 11-mar-25
    * Search for data based on title.
    * Updated on 1-apr-25
    * This method allows searching data from Google search api based on the following parameters:
    * - title
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    private function searchGoogle($title)
    {
        // $apiKey = 'AIzaSyAxO2TWy6DOkl_8yLYSe3gy58oQNAq5edo';
        // $cx = 'c5b2f0643f4b54394';
        $apiKey = env('GOOGLE_API_KEY');
        $cx = env('GOOGLE_SEARCH_CX');

        $client = new Client();
        $encodedTitle = urlencode($title);

        try {
            $response = $client->get('https://www.googleapis.com/customsearch/v1', [
                'query' => [
                    'key' => $apiKey,
                    'cx' => $cx,
                    'q' => $encodedTitle,
                    'num' => 10, // Get multiple results
                    'safe' => 'high',
                    'searchType' => 'image'
                ],
            ]);

            $googleSearchData = json_decode($response->getBody()->getContents(), true);

            $results = [];

            if (!empty($googleSearchData['items'])) {
                foreach ($googleSearchData['items'] as $item) {
                    $breadcrumb = $this->generateBreadcrumb($item['link']);

                    $results[] = [
                        'title'      => $item['title'],
                        'link'       => $item['image']['contextLink'] ?? $item['link'],
                        'snippet'    => $item['snippet'],
                        'image'      => $item['link'],
                        'breadcrumb' => $breadcrumb,
                    ];
                }
            }


            return response()->json($results);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Something went wrong',
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function generateBreadcrumb($url)
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        $segments = array_filter(explode('/', $path));

        $breadcrumb = array_map(function ($segment) {
            return ucwords(str_replace('-', ' ', $segment));
        }, $segments);

        // Add "Home" at start
        array_unshift($breadcrumb, 'Home');

        // Join with " > "
        return implode(' > ', $breadcrumb);
    }


    /*
    * Date: 12-mar-25
    * Search Product based on title in Ebay.
    *
    * This method allows searching Product from Ebay api based on the following parameters:
    * - title
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    private function searchEbay($title)
    {
        $client = new Client();
        $encodedTitle = urlencode($title);

        $ebayAppId = 'SachinMi-Zillionl-SBX-e68e0d05d-3408f0ad';

        try {
            //for sandbox
            $response = $client->get('https://svcs.sandbox.ebay.com/services/search/FindingService/v1', [
                //for production    $response = $client->get('https://svcs.ebay.com/services/search/FindingService/v1', [
                'query' => [
                    'OPERATION-NAME' => 'findItemsByKeywords',
                    'SERVICE-VERSION' => '1.0.0',
                    'SECURITY-APPNAME' => $ebayAppId,
                    'RESPONSE-DATA-FORMAT' => 'JSON',
                    'REST-PAYLOAD' => '',
                    'keywords' => $encodedTitle,
                    'paginationInput.entriesPerPage' => 5,
                ],
            ]);

            $ebayData = json_decode($response->getBody()->getContents(), true);

            $results = [];
            if (isset($ebayData['findItemsByKeywordsResponse'][0]['searchResult'][0]['item'])) {
                foreach ($ebayData['findItemsByKeywordsResponse'][0]['searchResult'][0]['item'] as $item) {
                    $results[] = [
                        'title' => $item['title'][0] ?? '',
                        'link' => $item['viewItemURL'][0] ?? '',
                        'price' => $item['sellingStatus'][0]['currentPrice'][0]['__value__'] ?? 'N/A',
                        'currency' => $item['sellingStatus'][0]['currentPrice'][0]['@currencyId'] ?? '',
                        'image' => $item['galleryURL'][0] ?? '',
                    ];
                }
            }

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }


    /*
    * Date: 13-mar-25
    * Search is based on title in YouTube.
    *
    * This method allows searching data from YouTube api based on the following parameters:
    * - title
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    private function searchYouTube($title)
    {
        $apiKey = 'AIzaSyA8ac-bB65BPHAdvPOOd3wshV2XpgtWD-s';
        $client = new Client();
        $encodedTitle = urlencode($title);

        try {
            $response = $client->get('https://www.googleapis.com/youtube/v3/search', [
                'query' => [
                    'part' => 'snippet',
                    'q' => $encodedTitle,
                    'key' => $apiKey,
                    'type' => 'video',
                    'maxResults' => 5,
                ],
            ]);

            $youtubeData = json_decode($response->getBody()->getContents(), true);
            $results = [];

            if (isset($youtubeData['items'])) {
                foreach ($youtubeData['items'] as $item) {
                    $results[] = [
                        'title' => $item['snippet']['title'],
                        'link' => 'https://www.youtube.com/watch?v=' . $item['id']['videoId'],
                        'thumbnail' => $item['snippet']['thumbnails']['default']['url'] ?? '',
                    ];
                }
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }


    /*
    * Date: 13-mar-25
    * Search is based on title in Amazon.
    *
    * This method allows searching data from Amazon api based on the following parameters:
    * - title
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    private function searchAmazon($title)
    {
        return "https://www.amazon.com/s?k=" . urlencode($title);
    }

    /*
    * Date: 20-mar-25
    * Updated: 17-apr-25
    * Search is based on title in database.
    *
    * This method allows searching data from database based on title:
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function search_bookmark(Request $request)
    {
        try {

            $userId = auth::id();
            $query = Bookmark::query()
                ->select('user_bookmarks.id', 'website_url', 'icon_path', 'title')
                ->join('user_bookmarks', 'bookmarks.id', '=', 'user_bookmarks.bookmark_id')
                ->where('bookmarks.user_id', $userId);

            if ($request->has('title')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->title . '%')
                        ->orWhere('website_url', 'like', '%' . $request->title . '%');
                });
            }

            $bookmarks = $query->get();

            if ($bookmarks->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No bookmarks found for the given title.',
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bookmarks retrieved successfully.',
                'data' => [
                    'bookmarks' => $bookmarks,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while fetching bookmarks.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
