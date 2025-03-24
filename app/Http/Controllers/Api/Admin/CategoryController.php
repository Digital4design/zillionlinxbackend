<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
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
                $categories = Category::whereNull('parent_id')->with('subcategories')->get();

                if ($categories->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No categories found.'
                    ], 404);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new category
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|unique:categories',
            // 'slug' => 'required|string|unique:categories',
            'parent_id' => 'nullable|exists:categories,id', // Ensures parent exists
        ]);

        $category = Category::create([
            'title' => $request->title,
            'slug' => $request->slug,
            'parent_id' => $request->parent_id, // This allows subcategories
        ]);

        // Return full category details including its subcategories
        return response()->json([
            'message' => 'Category created successfully!',
            'category' => Category::with('subcategories')->find($category->id),
        ]);
    }
    // Get a single category
    public function show($id)
    {
        return response()->json(Category::findOrFail($id));
    }

    /*
    * Date: 24-Mar-2025
    * Update Category and Sub-category Data.
    *
    * This method allows Updating a Category based on the following parameter:
    * - ID
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->all());

        return response()->json(['message' => 'Category updated successfully!', 'category' => $category]);
    }


    /*
    * Date: 24-Mar-2025
    * Delete Category Data.
    *
    * This method allows deleting a Category based on the following parameter:
    * - ID
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function destroy(Request $request)
    {
        try {
            $ids = $request->input('ids'); // Expecting an array or a single ID

            if (is_array($ids)) {
                // Delete multiple categories
                $deleted = Category::whereIn('id', $ids)->delete();
            } else {
                // Delete single category
                $deleted = Category::where('id', $ids)->delete();
            }

            if ($deleted) {
                return response()->json(['message' => 'Category deleted successfully!']);
            } else {
                return response()->json(['error' => 'Category not found or could not be deleted!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred!', 'message' => $e->getMessage()], 500);
        }
    }
}
