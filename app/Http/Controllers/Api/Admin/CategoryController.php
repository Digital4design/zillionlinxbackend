<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Exception;

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
            $search = $request->query('title'); // Get search query
            $perPage = $request->query('per_page', 20); // Get per_page query, default 20

            $query = Category::whereNull('user_id')->orderBy('position', 'asc');

            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id')->with('adminsubcategories');
            }

            // if ($search) {
            //     $query->where('title', 'LIKE', "%$search%");
            // }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")
                        ->orWhereHas('adminsubcategories', function ($subQuery) use ($search) {
                            $subQuery->where('title', 'LIKE', "%$search%");
                        });
                });
            }

            $categories = $query->paginate($perPage);

            if ($categories->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No categories found.'
                ], 404);
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
            $ids = $request->input('ids');

            if (is_array($ids)) {
                $parentIds = Category::whereIn('parent_id', $ids)->pluck('id')->toArray();
                $allIdsToDelete = array_merge($ids, $parentIds);
                $deleted = Category::whereIn('id', $allIdsToDelete)->delete();
            } else {
                $parentIds = Category::where('parent_id', $ids)->pluck('id')->toArray();
                $deleted = Category::whereIn('id', array_merge([$ids], $parentIds))->delete();
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

    /*
    * Date: 31-Mar-2025
    * reorderCategory.
    *
    * This method allows reorder Categories based on the following parameter:
    * - ID
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function reorderCategory(Request $request)
    {
        try {
            $order = $request->input('order'); // Array of IDs in new order

            if (!$order || !is_array($order)) {
                return response()->json(['status' => 400, 'message' => 'Invalid order data'], 400);
            }

            foreach ($order as $index => $id) {
                Category::where('id', $id)->update(['position' => $index + 1]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Category reordered successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    * Date: 17-Apr-2025
    * get all categories.
    *
    * This method allows to get all categories
    * @return \Illuminate\Http\JsonResponse
    */
    public function get_category()
    {
        $category = Category::where('parent_id', null)
            ->where('user_id', null)
            ->select('id', 'title')
            ->orderBy('position', 'asc')
            ->get();

        if ($category->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No categories found'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Category fetched successfully',
            'data' => $category
        ], 200);
    }
}
