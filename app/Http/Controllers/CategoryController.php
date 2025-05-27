<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\Product;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Products/Category', [
            'categories' => ProductCategory::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'remark' => 'required|string|max:255',
        ]);

        $category = ProductCategory::create($validated);
        return response()->json($category);
        // return redirect()->route('categories.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name,' . $id,
            'remark' => 'required|string|max:255',
        ]);

        $category = ProductCategory::findOrFail($id);
        $category->update($validated);
        return response()->json($category);
        // return redirect()->route('categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $category)
    {
        if (Product::where('category_id', $category->id)->exists()) {
            return response()->json(['message' => 'Category cannot be deleted because it is used in a product'], 400);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
