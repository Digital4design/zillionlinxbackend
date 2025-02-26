<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
   
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
    
}
