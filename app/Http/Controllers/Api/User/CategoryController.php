<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
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

    // public function index(Request $request)
    // {
    //     $userId = Auth::id(); // Get authenticated user ID

    //     $categories = DB::table('categories as c1')
    //         ->leftJoin('categories as c2', 'c1.id', '=', 'c2.parent_id')
    //         ->select(
    //             'c1.id as category_id',
    //             'c1.user_id as category_user_id',
    //             'c1.title as category_title',
    //             'c1.slug as category_slug',
    //             'c1.parent_id as category_parent_id',
    //             'c1.position as category_position',
    //             'c1.created_at as category_created_at',
    //             'c1.updated_at as category_updated_at',
    //             'c2.id as subcategory_id',
    //             'c2.user_id as subcategory_user_id',
    //             'c2.title as subcategory_title',
    //             'c2.slug as subcategory_slug',
    //             'c2.parent_id as subcategory_parent_id',
    //             'c2.position as subcategory_position',
    //             'c2.created_at as subcategory_created_at',
    //             'c2.updated_at as subcategory_updated_at'
    //         )
    //         ->whereNull('c1.parent_id') // Only fetch main categories
    //         ->where(function ($query) use ($userId) {
    //             $query->whereNull('c1.user_id')
    //                 ->orWhere('c1.user_id', $userId);
    //         })
    //         ->where(function ($query) use ($userId) {
    //             $query->whereNull('c2.user_id')
    //                 ->orWhere('c2.user_id', $userId);
    //         })
    //         ->orderBy('c1.id')
    //         ->orderBy('c2.id')
    //         ->get();

    //     // Formatting Data
    //     $formattedData = [];

    //     foreach ($categories as $category) {
    //         if (!isset($formattedData[$category->category_id])) {
    //             $formattedData[$category->category_id] = [
    //                 'id' => $category->category_id,
    //                 'user_id' => $category->category_user_id,
    //                 'title' => $category->category_title,
    //                 'slug' => $category->category_slug,
    //                 'parent_id' => $category->category_parent_id,
    //                 'position' => $category->category_position,
    //                 'created_at' => $category->category_created_at,
    //                 'updated_at' => $category->category_updated_at,
    //                 'subcategories' => []
    //             ];
    //         }

    //         if ($category->subcategory_id) {
    //             $formattedData[$category->category_id]['subcategories'][] = [
    //                 'id' => $category->subcategory_id,
    //                 'user_id' => $category->subcategory_user_id,
    //                 'title' => $category->subcategory_title,
    //                 'slug' => $category->subcategory_slug,
    //                 'parent_id' => $category->subcategory_parent_id,
    //                 'position' => $category->subcategory_position,
    //                 'created_at' => $category->subcategory_created_at,
    //                 'updated_at' => $category->subcategory_updated_at
    //             ];
    //         }
    //     }

    //     // Convert array values to list
    //     $response = [
    //         'success' => true,
    //         'data' => array_values($formattedData)
    //     ];

    //     // Return JSON response
    //     return response()->json($response);
    // }

    public function index(Request $request)
    {
        $userId = Auth::id(); // Get authenticated user ID
        $parentId = $request->input('parent_id'); // Get parent_id from request

        $query = DB::table('categories as c1')
            ->leftJoin('categories as c2', 'c1.id', '=', 'c2.parent_id')
            ->select(
                'c1.id as category_id',
                'c1.user_id as category_user_id',
                'c1.title as category_title',
                'c1.slug as category_slug',
                'c1.parent_id as category_parent_id',
                'c1.position as category_position',
                'c1.created_at as category_created_at',
                'c1.updated_at as category_updated_at',
                'c2.id as subcategory_id',
                'c2.user_id as subcategory_user_id',
                'c2.title as subcategory_title',
                'c2.slug as subcategory_slug',
                'c2.parent_id as subcategory_parent_id',
                'c2.position as subcategory_position',
                'c2.created_at as subcategory_created_at',
                'c2.updated_at as subcategory_updated_at'
            )
            ->where(function ($query) use ($userId) {
                $query->whereNull('c1.user_id')
                    ->orWhere('c1.user_id', $userId);
            })
            ->where(function ($query) use ($userId) {
                $query->whereNull('c2.user_id')
                    ->orWhere('c2.user_id', $userId);
            });

        if ($parentId) {
            // Fetch only subcategories of the given parent_id
            $query->where('c1.parent_id', $parentId);
        } else {
            // Fetch only main categories (parent_id = NULL)
            $query->whereNull('c1.parent_id');
        }

        $query->orderBy('c1.id')->orderBy('c2.id');

        $categories = $query->get();

        // Formatting Data
        $formattedData = [];

        foreach ($categories as $category) {
            if (!isset($formattedData[$category->category_id])) {
                $formattedData[$category->category_id] = [
                    'id' => $category->category_id,
                    'user_id' => $category->category_user_id,
                    'title' => $category->category_title,
                    'slug' => $category->category_slug,
                    'parent_id' => $category->category_parent_id,
                    'position' => $category->category_position,
                    'created_at' => $category->category_created_at,
                    'updated_at' => $category->category_updated_at,
                    'subcategories' => []
                ];
            }

            if ($category->subcategory_id) {
                $formattedData[$category->category_id]['subcategories'][] = [
                    'id' => $category->subcategory_id,
                    'user_id' => $category->subcategory_user_id,
                    'title' => $category->subcategory_title,
                    'slug' => $category->subcategory_slug,
                    'parent_id' => $category->subcategory_parent_id,
                    'position' => $category->subcategory_position,
                    'created_at' => $category->subcategory_created_at,
                    'updated_at' => $category->subcategory_updated_at
                ];
            }
        }

        // Convert array values to list
        $response = [
            'success' => true,
            'data' => array_values($formattedData)
        ];

        // Return JSON response
        return response()->json($response);
    }
}
