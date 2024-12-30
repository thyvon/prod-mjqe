<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    // Display all products with their categories and groups
    public function index()
    {
        $products = Product::with(['category:id,name', 'group:id,name'])->get(); // Only load necessary fields
        $categories = ProductCategory::all();
        $groups = ProductGroup::all();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'groups' => $groups,
        ]);
    }

    // Show form for creating a new product
    public function create()
    {
        $categories = ProductCategory::all();
        $groups = ProductGroup::all();

        return Inertia::render('Products/Create', [
            'categories' => $categories,
            'groups' => $groups
        ]);
    }

    // Store a new product
    public function store(Request $request)
    {
        // dd('Store method reached');
        // Validation...
        $validated = $request->validate([
            // 'sku' => 'required|string|max:255',
            'product_description' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'group_id' => 'required|exists:product_groups,id',
            'price' => 'required|numeric',
            'uom'   => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'status' => 'required|in:0,1', // Validating status as 0 or 1
        ]);

        // Debugging - check validated data
        // dd($request->all());

        // If creating a new product
        $product = Product::create($validated);

        // Return success response
        // return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }


    // Show the form for editing the specified product
    public function edit(Product $product)
    {
        $categories = ProductCategory::all();
        $groups = ProductGroup::all();

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'groups' => $groups
        ]);
    }

    // Update the specified product
    public function update(Request $request, Product $product)
    {
        // Validation...
        $validated = $request->validate([
            // 'sku' => 'required|string|max:255',
            'product_description' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'group_id' => 'required|exists:product_groups,id',
            'price' => 'required|numeric',
            'uom'   => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'status' => 'required|in:0,1',
        ]);

        unset($validated['sku']);
        // Update the product
        $product->update($validated);

        // Return success response
        // return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    // Delete the specified product
    public function destroy(Product $product)
    {
        $product->delete();

        // Redirect with success message
        // return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }
}
