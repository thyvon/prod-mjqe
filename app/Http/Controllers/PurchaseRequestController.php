<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\PrItem;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use Illuminate\Validation\Rule;

class PurchaseRequestController extends Controller
{
    // Display a list of purchase requests
    public function index()
    {
        $purchaseRequests = PurchaseRequest::with(['prItems', 'requestBy'])->get();

        return Inertia::render('Purchase/Pr/Index', [
            'purchaseRequests' => $purchaseRequests,
        ]);
    }

    // Show the form to create a new purchase request
    public function create()
    {
        $currentUser = auth()->user();
        $products = Product::all();

        return Inertia::render('Purchase/Pr/Create', [
            'currentUser' => $currentUser,
            'products' => $products,
        ]);
    }

    // Store a newly created purchase request with items
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pr_number' => 'required|string|unique:purchase_requests',
            'request_date' => 'required|date',
            'request_by' => 'required|exists:users,id',
            'campus' => 'required|string',
            'division' => 'required|string',
            'department' => 'required|string',
            'purpose' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.remark' => 'nullable|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.uom' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'items.*.campus' => 'required|string',
            'items.*.division' => 'required|string',
            'items.*.department' => 'required|string',
        ]);

        try {
            $purchaseRequest = PurchaseRequest::create($validated);

            // Create PR Items
            foreach ($validated['items'] as $item) {
                $purchaseRequest->prItems()->create($item);
            }

            return redirect()->route('purchase-requests.index')->with('success', 'Purchase request created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating purchase request.')->withInput();
        }
    }

    // Show the form to edit an existing purchase request
// Show the form to edit an existing purchase request
        public function edit($id)
        {
            $purchaseRequest = PurchaseRequest::with('prItems.product')->findOrFail($id);
            $currentUser = auth()->user();
            $products = Product::all();
            //dd($purchaseRequest->prItems);

            return Inertia::render('Purchase/Pr/Edit', [
                'purchaseRequest' => $purchaseRequest,
                'currentUser' => $currentUser,
                'products' => $products,
            ]);
        }


        public function show($id)
        {
            // Fetch the Purchase Request by ID, including its related PR items
            $purchaseRequest = PurchaseRequest::with('prItems.product')->findOrFail($id);

            // Get all available products to display in the frontend
            $products = Product::all();

            // Return the view with the PR data and associated products
            return Inertia::render('PurchaseRequests/Show', [
                'purchaseRequest' => $purchaseRequest,
                'products' => $products
            ]);
        }


    // Update an existing purchase request with items
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pr_number' => [
                'required',
                'string',
                Rule::unique('purchase_requests', 'pr_number')->ignore($id),
            ],
            'request_date' => 'required|date',
            'request_by' => 'required|exists:users,id',
            'campus' => 'required|string',
            'division' => 'required|string',
            'department' => 'required|string',
            'purpose' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:pr_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.remark' => 'nullable|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.uom' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'items.*.campus' => 'required|string',
            'items.*.division' => 'required|string',
            'items.*.department' => 'required|string',
        ]);

        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->update($validated);

            // Sync or update PR Items
            $existingItemIds = collect($validated['items'])->pluck('id')->filter();
            $purchaseRequest->prItems()->whereNotIn('id', $existingItemIds)->delete(); // Delete removed items
            foreach ($validated['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    PrItem::find($itemData['id'])->update($itemData); // Update existing item
                } else {
                    $purchaseRequest->prItems()->create($itemData); // Create new item
                }
            }

            return redirect()->route('purchase-requests.index')->with('success', 'Purchase request updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating purchase request.')->withInput();
        }
    }

    // Delete a purchase request and its items
    public function destroy($id)
    {
        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->prItems()->delete(); // Delete associated items
            $purchaseRequest->delete();

            return redirect()->route('purchase-requests.index')->with('success', 'Purchase request deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting purchase request.');
        }
    }
}
