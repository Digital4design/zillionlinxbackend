<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Bookmark;
use App\Models\UserBookmark;
use Carbon\Carbon;

class DashboardController extends Controller
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

    /*
    * Date: 24-Mar-2025
    *
    * This method allows show the dashboard data:
    * - total users, bookmark, email, password, country
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function index(Request $request)
    {

        try {

            $totalUsers = User::where('role_id', 2)->count();
            $users = User::where('role_id', 2)->orderBy('created_at', 'desc')->limit(50)->get();
            $totalBookmark = Bookmark::count();
            $bookmarks = UserBookmark::with('bookmark')
                ->orderBy('position', 'asc') // Then order by position
                ->limit(50)
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
                'status' => 'success',
                'total_users' => $totalUsers,
                'user_data' => $users,
                'totalBookmark' => $totalBookmark,
                'bookmark_data' => $bookmarks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again later.',
                'status' => 'error',
                'status_code' => 500,
            ], 500);
        }
    }

    /*
    * Date: 25-Mar-2025
    *
    * This method allows show the sixMonthsUser count:
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function sixMonthsUser()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[Carbon::now()->subMonths($i)->format('Y-m')] = 0;
        }

        // Query to get user counts per month
        $sixMonthsUser = User::where('role_id', 2)
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth()) // Ensure correct date range
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('count', 'month') // Convert to array
            ->toArray();

        // Merge actual counts into our predefined months
        $finalCounts = array_merge($months, $sixMonthsUser);

        // Convert to array of objects for JSON response
        $responseData = [];
        foreach ($finalCounts as $month => $count) {
            $responseData[] = ['month' => $month, 'count' => $count];
        }

        // Return JSON response
        return response()->json([
            'status' => 'success',
            'six_months_user' => $responseData,
        ], 200);
    }


    /*
    * Date: 26-Mar-2025
    *
    * This method allows show the sixMonthsBookmarks count:
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function sixMonthsBookmarks()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[Carbon::now()->subMonths($i)->format('Y-m')] = 0;
        }

        // Query to get user counts per month
        $sixMonthsBookmark = Bookmark::where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth()) // Ensure correct date range
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('count', 'month') // Convert to array
            ->toArray();

        // Merge actual counts into our predefined months
        $finalCounts = array_merge($months, $sixMonthsBookmark);

        // Convert to array of objects for JSON response
        $responseData = [];
        foreach ($finalCounts as $month => $count) {
            $responseData[] = ['month' => $month, 'count' => $count];
        }

        // Return JSON response
        return response()->json([
            'status' => 'success',
            'six_months_bookmark' => $responseData,
        ], 200);
    }
}
