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
    * Last Updated: 12-mar-25
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

        // searching 
        $googleResults = [];
        $wikimediaResults = [];
        $ebayResults = [];

        if ($request->has('title')) {
            $googleResults = $this->searchGoogle($request->title);
            $wikimediaResults = $this->searchWikimedia($request->title);
            $ebayResults = $this->searchEbay($request->title);
        }

        // Return a response with both the bookmarks and Google search results
        return response()->json([
            'success' => true,
            'data' => [
                'bookmarks' => $bookmarks,
                'google_search_results' => $googleResults,
                'wikimedia_search_results' => $wikimediaResults,
                'ebay_search_results' => $ebayResults,
            ],
        ]);
    }

    /*
    * Date: 11-mar-25
    * Search for data based on title.
    *
    * This method allows searching data from Google search api based on the following parameters:
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
    * Search for data based on title.
    *
    * This method allows searching data from Wikimedia api based on the following parameters:
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
}
