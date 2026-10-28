<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\MenuItem;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', 1)->get();
        $menuItems = MenuItem::with('category')->where('is_active', 1)->orderBy('menuID', 'desc')->get();
        
        return view('components.menu', compact('categories', 'menuItems'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'itemname' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'categoryID' => 'required|integer'
        ]);

        $item = MenuItem::create($request->all());

        $item->load('category'); 

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function update(Request $request, $id)
    {
        $item = MenuItem::findOrFail($id);
        $item->update($request->all());
        $item->load('category');

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function destroy($id)
    {
        $item = MenuItem::findOrFail($id);
        $item->update(['is_active' => 0]); 

        return response()->json(['success' => true]);
    }
}