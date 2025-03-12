<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bookmark;
use GuzzleHttp\Client;


class SearchController extends Controller
{

    /*
    * Date: 11-mar-25
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

        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        $bookmarks = $query->select('website_url', 'icon_path')->get();

        // searching in google search function
        $googleResults = [];
        $wikimediaResults = [];

        if ($request->has('title')) {
            $googleResults = $this->searchGoogle($request->title);
            $wikimediaResults = $this->searchWikimedia($request->title);
        }

        // Return a response with both the bookmarks and Google search results
        return response()->json([
            'success' => true,
            'data' => [
                'bookmarks' => $bookmarks,
                'google_search_results' => $googleResults,
                'wikimedia_search_results' => $wikimediaResults,
            ],
        ]);
    }

    /*
    * Date: 11-mar-25
    * Search for bookmarks based on title.
    *
    * This method allows searching bookmarks from Google search api based on the following parameters:
    * - title
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    private function searchGoogle($title)
    {

        $apiKey = 'AIzaSyAxO2TWy6DOkl_8yLYSe3gy58oQNAq5edo';
        $cx = 'c5b2f0643f4b54394';

        $client = new Client();

        $encodedTitle = urlencode($title);

        try {
            $response = $client->get('https://www.googleapis.com/customsearch/v1', [
                'query' => [
                    'key' => $apiKey,
                    'cx' => $cx,
                    'q' => $encodedTitle,
                ],
            ]);

            $googleSearchData = json_decode($response->getBody()->getContents(), true);

            $results = [];
            if (isset($googleSearchData['items'])) {
                foreach ($googleSearchData['items'] as $item) {
                    $results[] = [
                        'title' => $item['title'],
                        'link' => $item['link'],
                        'snippet' => $item['snippet'],
                    ];
                }
            }

            return $results;
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Something went wrong',
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /*
    * Date: 11-mar-25
    * Search for bookmarks based on title.
    *
    * This method allows searching bookmarks from Wikimedia api based on the following parameters:
    * - title
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    private function searchWikimedia($title)
    {
        $client = new Client();
        $encodedTitle = urlencode($title);

        try {

            $response = $client->get('https://en.wikipedia.org/w/api.php', [
                'query' => [
                    'action' => 'query',
                    'format' => 'json',
                    'list' => 'search',
                    'srsearch' => $encodedTitle,
                    'utf8' => 1,
                ],
            ]);

            $wikimediaData = json_decode($response->getBody()->getContents(), true);

            $results = [];
            if (isset($wikimediaData['query']['search'])) {
                foreach ($wikimediaData['query']['search'] as $item) {
                    $results[] = [
                        'title' => $item['title'],
                        'link' => 'https://en.wikipedia.org/?curid=' . $item['pageid'],
                        'snippet' => strip_tags($item['snippet']),
                    ];
                }
            }

            return $results;
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Something went wrong',
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
}
