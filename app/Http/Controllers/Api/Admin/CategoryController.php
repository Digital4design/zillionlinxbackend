<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::all());
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

    // Update a category
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->all());

        return response()->json(['message' => 'Category updated successfully!', 'category' => $category]);
    }  
    

    // Delete a category
    public function destroy($id)
    {
        Category::findOrFail($id)->delete();
        return response()->json(['message' => 'Category deleted successfully!']);
    }
}
