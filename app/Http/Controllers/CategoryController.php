<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Load the Categories page
    public function index()
    {
        $categories = Category::where('is_active', 1)->orderBy('categoryID', 'desc')->get();
        return view('components.categories', compact('categories'));
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => 0]); 

        return response()->json(['success' => true]);
    }

    // Save a new Category
    public function store(Request $request)
    {
        $request->validate([
            'categoryname' => 'required|string|max:255'
        ]);

        $category = Category::create([
            'categoryname' => $request->categoryname
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    // Update an existing Category
    public function update(Request $request, $id)
    {
        $request->validate([
            'categoryname' => 'required|string|max:255'
        ]);

        $category = Category::findOrFail($id);
        $category->update([
            'categoryname' => $request->categoryname
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }
}