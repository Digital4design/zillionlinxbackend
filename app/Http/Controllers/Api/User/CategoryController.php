<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

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

    public function index(Request $request)
    {
        try {
            $parentId = $request->query('parent_id');

            if ($parentId) {
                $categories = Category::where('parent_id', $parentId)->get();
                if ($categories->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No subcategories found for this parent_id.'
                    ], 404);
                }
            } else {
                $userId = Auth::id(); // Get the logged-in user's ID

                $categories = Category::select('categories.id', 'categories.user_id', 'categories.title', 'categories.slug', 'categories.parent_id', 'categories.position', 'categories.created_at', 'categories.updated_at')
                    ->whereNull('categories.parent_id')  // Only top-level categories
                    ->leftJoin('categories as subcategories', function ($join) use ($userId) {
                        $join->on('categories.id', '=', 'subcategories.parent_id')
                            ->where(function ($q) use ($userId) {
                                $q->whereNull('subcategories.user_id')
                                    ->orWhere('subcategories.user_id', $userId);
                            });
                    })
                    ->get()
                    ->map(function ($category) {
                        $category->subcategories = Category::where('parent_id', $category->id)
                            ->where(function ($q) {
                                $q->whereNull('user_id')
                                    ->orWhere('user_id', Auth::id());
                            })
                            ->get();
                        return $category;
                    });

                return response()->json([
                    'success' => true,
                    'data' => $categories
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
