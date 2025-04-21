<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use App\Models\PurchaseInvoiceItem;
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

    // Store a new product
    public function store(Request $request)
    {
        // Debugging: Log the request data
        \Log::info('Store Product Request Data:', $request->all());

        // Validation...
        try {
            $validated = $request->validate([
                'product_description' => 'required|string|max:255',
                'brand' => 'required|string|max:255',
                'category_id' => 'required|exists:product_categories,id',
                'group_id' => 'required|exists:product_groups,id',
                'price' => 'required|numeric',
                'uom'   => 'required|string|max:255',
                'quantity' => 'required|numeric',
                'status' => 'required|in:0,1', // Validating status as 0 or 1
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Errors:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Debugging: Log the validated data
        \Log::info('Validated Product Data:', $validated);

        // If creating a new product
        $product = Product::create($validated);

        // Load the relationships
        $product->load(['category:id,name', 'group:id,name']);

        // Return success response
        return response()->json($product, 201);
    }

    // Update the specified product
    public function update(Request $request, Product $product)
    {
        // Validation...
        try {
            $validated = $request->validate([
                'product_description' => 'required|string|max:255',
                'brand' => 'required|string|max:255',
                'category_id' => 'required|exists:product_categories,id',
                'group_id' => 'required|exists:product_groups,id',
                'price' => 'required|numeric',
                'uom'   => 'required|string|max:255',
                'quantity' => 'required|numeric',
                'status' => 'required|in:0,1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Errors:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Update the product
        $product->update($validated);

        // Load the relationships
        $product->load(['category:id,name', 'group:id,name']);

        // Return success response
        return response()->json($product);
    }

    public function show($id)
    {
        // Fetch the product with its category and group relationships
        $product = Product::with(['category:id,name', 'group:id,name'])->find($id);
        $purchasedItems = PurchaseInvoiceItem::with(['product:id,sku,product_description', 'purchasedBy:id,name', 'supplier:id,name', 'invoice:id,pi_number'])->where('item_code', $product->id)->get();

        // Check if the product exists
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        // dd ($purchasedItems);

        return Inertia::render('Products/Show', [
            'product' => $product,
            'purchasedItems' => $purchasedItems,
        ]);
    }

    // Delete the specified product
    public function destroy(Product $product)
    {
        $product->delete();

        // Return success response
        return response()->json(['message' => 'Product deleted successfully!']);
    }
}
