<?php

namespace App\Http\Controllers;

use App\Models\PrItem;
use App\Models\PurchaseRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $prItems = PrItem::with(['purchaseRequest', 'product'])
            ->where('pr_id', 37) // Filter items by pr_id = 37
            ->get();

        return Inertia::render('PrItems/Index', [
            'prItems' => $prItems,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::all();  // Get all products for the select options
        $purchaseRequests = PurchaseRequest::all();  // Get all purchase requests

        return Inertia::render('PrItems/Create', [
            'products' => $products,
            'purchaseRequests' => $purchaseRequests,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pr_id' => 'required|exists:purchase_requests,id',
            'product_id' => 'required|exists:products,id',
            'remark' => 'nullable|string',
            'qty' => 'required|numeric',
            'uom' => 'required|string',
            'price' => 'required|numeric',
            'total_price' => 'required|numeric',
            'campus' => 'required|string',
            'division' => 'required|string',
            'department' => 'required|string',
            'status' => 'required|string',
        ]);

        $prItem = PrItem::create($request->all());

        return redirect()->route('pr-items.index')->with('success', 'PR Item created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $prItem = PrItem::with(['purchaseRequest', 'product'])->findOrFail($id);

        return Inertia::render('PrItems/Show', [
            'prItem' => $prItem,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $prItem = PrItem::findOrFail($id);
        $products = Product::all();  // Get all products for the select options
        $purchaseRequests = PurchaseRequest::all();  // Get all purchase requests

        return Inertia::render('PrItems/Edit', [
            'prItem' => $prItem,
            'products' => $products,
            'purchaseRequests' => $purchaseRequests,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'pr_id' => 'required|exists:purchase_requests,id',
            'product_id' => 'required|exists:products,id',
            'remark' => 'nullable|string',
            'qty' => 'required|numeric',
            'uom' => 'required|string',
            'price' => 'required|numeric',
            'total_price' => 'required|numeric',
            'campus' => 'required|string',
            'division' => 'required|string',
            'department' => 'required|string',
            'status' => 'required|string',
        ]);

        $prItem = PrItem::findOrFail($id);
        $prItem->update($request->all());

        return redirect()->route('pr-items.index')->with('success', 'PR Item updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $prItem = PrItem::findOrFail($id);
        $prItem->delete();

        return redirect()->route('pr-items.index')->with('success', 'PR Item deleted successfully');
    }
}
