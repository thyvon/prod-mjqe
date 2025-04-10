<?php

namespace App\Http\Controllers;

use App\Models\Cancellation;
use App\Models\CancellationItems;
use App\Models\PrItem;
use App\Models\PoItems;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CancallationController extends Controller
{
    // Display a listing of cancellations
    public function index()
    {
        $cancellations = Cancellation::with('items', 'user')->latest()->get(); // Ensure latest data is fetched
        return Inertia::render('Cancellations/Index', [
            'cancellations' => $cancellations,
        ]);
    }

    // Ensure the getPrItems method is accessible via an API route
    public function getPrItems(Request $request)
    {
        $prItems = PrItem::with(['product:id,product_description,sku', 'purchaseRequest:id,pr_number'])
            ->whereHas('purchaseRequest', function ($query) {
                $query->where('request_by', auth()->id());
            })
            ->where('qty_pending', '!=', 0) // Ensure only items with pending quantity are fetched
            ->get();

        return response()->json($prItems); // Ensure PR numbers are included
    }

    public function getPoItems(Request $request)
    {
        $poItems = PoItems::with(['product:id,product_description,sku', 'purchaseOrder:id,po_number','purchaseRequest:id,pr_number'])
            ->whereHas('purchaseOrder', function ($query) {
                $query->where('purchased_by', auth()->id());
            })
            ->where('pending', '!=', 0) // Ensure only items with pending quantity are fetche
            ->get();

        return response()->json($poItems); // Ensure PO numbers are included
    }

    // Show the form for creating a new cancellation
    public function create()
    {
        return Inertia::render('Cancellations/Create');
    }

    // Store a newly created cancellation
    public function store(Request $request)
    {
        \Log::info('Store method called with data:', $request->all()); // Log the incoming request data

        try {
            $validated = $request->validate([
                'cancellation_date' => 'required|date',
                'cancellation_docs' => 'required|integer',
                'cancellation_reason' => 'nullable|string',
                'pr_po_id' => 'nullable|integer', // Add validation for pr_po_id
                'items' => 'required|array',
                'items.*.purchase_order_id' => 'nullable|exists:purchase_orders,id',
                'items.*.purchase_order_item_id' => 'nullable|exists:po_items,id',
                'items.*.purchase_request_id' => 'nullable|exists:purchase_requests,id',
                'items.*.purchase_request_item_id' => 'nullable|exists:pr_items,id',
                'items.*.cancellation_reason' => 'nullable|string', // Validate cancellation_reason for each item
                'items.*.qty' => 'required', // Added validation for qty
            ]);

            \Log::info('Validated data:', $validated); // Log the validated data

            $validated['cancellation_by'] = auth()->id(); // Automatically set the authenticated user
            $validated['cancellation_no'] = Cancellation::generateCancellationNo();

            // Ensure pr_po_id is included in the creation
            $cancellation = Cancellation::create([
                'cancellation_date' => $validated['cancellation_date'],
                'cancellation_docs' => $validated['cancellation_docs'],
                'cancellation_reason' => $validated['cancellation_reason'],
                'pr_po_id' => $validated['pr_po_id'], // Include pr_po_id
                'cancellation_by' => $validated['cancellation_by'], // Set cancellation_by
                'cancellation_no' => $validated['cancellation_no'],
            ]);

            if (isset($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $item['cancellation_id'] = $cancellation->id;
                    $item['cancellation_by'] = $validated['cancellation_by'];
                    $item['purchase_order_id'] = $item['purchase_order_id'] ?? null;
                    $item['purchase_order_item_id'] = $item['purchase_order_item_id'] ?? null;
                    CancellationItems::create($item); // Save cancellation_reason along with other fields
                }
            }

            \Log::info('Cancellation created successfully:', $cancellation->toArray()); // Log the created cancellation

            return response()->json([
                'cancellation' => $cancellation->load('items', 'user'),
                'message' => 'Cancellation created successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in store method:', ['errors' => $e->errors()]); // Log validation errors
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error in store method:', ['error' => $e->getMessage()]); // Log unexpected errors
            return response()->json(['message' => 'Failed to create cancellation.'], 500);
        }
    }

    // Display the specified cancellation
    public function show($id)
    {
        $cancellation = Cancellation::with('items', 'user')->findOrFail($id);
        return Inertia::render('Cancellations/Show', [
            'cancellation' => $cancellation,
        ]);
    }

    // Show the form for editing the specified cancellation
    public function edit($id)
    {
        \Log::info('Edit method called for cancellation ID:', ['id' => $id]); // Log the ID of the cancellation being edited

        try {
            $cancellation = Cancellation::with([
                'items.purchaseRequestItem.product',
                'items.purchaseRequestItem.purchaseRequest',
                'items.purchaseOrderItem.product',
                'items.purchaseOrderItem.purchaseOrder',
            ])->findOrFail($id);

            // Log the retrieved cancellation data
            \Log::info('Cancellation retrieved from database:', $cancellation->toArray());

            // Map items to include only necessary fields
            $cancellation->items = $cancellation->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->purchaseRequestItem?->product?->product_description 
                              ?? $item->purchaseOrderItem?->product?->product_description,
                    'pr_number' => $item->purchaseRequestItem?->purchaseRequest?->pr_number 
                                   ?? $item->purchaseOrderItem?->purchaseRequest?->pr_number,
                    'po_number' => $item->purchaseOrderItem?->purchaseOrder?->po_number,
                    'sku' => $item->purchaseRequestItem?->product?->sku 
                             ?? $item->purchaseOrderItem?->product?->sku,
                    'qty' => $item->qty,
                    'purchase_request_id' => $item->purchase_request_id,
                    'purchase_request_item_id' => $item->purchase_request_item_id,
                    'purchase_order_id' => $item->purchase_order_id,
                    'purchase_order_item_id' => $item->purchase_order_item_id,
                    'cancellation_reason' => $item->cancellation_reason,
                ];
            });

            // Explicitly include pr_po_id and cancellation_docs in the response
            $response = [
                'cancellation' => $cancellation,
                'pr_po_id' => $cancellation->pr_po_id,
                'cancellation_docs' => $cancellation->cancellation_docs,
            ];

            // Log the response data
            \Log::info('Response data for edit method:', $response);

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Error loading cancellation for edit:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to load cancellation data.'], 500);
        }
    }

    // Update the specified cancellation
    public function update(Request $request, $id)
    {
        \Log::info('Update method called with data:', $request->all()); // Log the incoming request data

        try {
            $validated = $request->validate([
                'cancellation_date' => 'sometimes|date',
                'cancellation_docs' => 'sometimes|integer',
                'cancellation_reason' => 'nullable|string',
                'pr_po_id' => 'nullable|integer', // Add validation for pr_po_id
                'items' => 'sometimes|array',
                'items.*.purchase_order_id' => 'nullable|exists:purchase_orders,id',
                'items.*.purchase_order_item_id' => 'nullable|exists:po_items,id',
                'items.*.purchase_request_id' => 'nullable|exists:purchase_requests,id',
                'items.*.purchase_request_item_id' => 'nullable|exists:pr_items,id',
                'items.*.cancellation_reason' => 'nullable|string', // Validate cancellation_reason for each item
                'items.*.qty' => 'required', // Added validation for qty
            ]);

            \Log::info('Validated data:', $validated); // Log the validated data

            $cancellation = Cancellation::findOrFail($id);

            // Ensure pr_po_id is explicitly updated
            $cancellation->update([
                'cancellation_date' => $validated['cancellation_date'] ?? $cancellation->cancellation_date,
                'cancellation_docs' => $validated['cancellation_docs'] ?? $cancellation->cancellation_docs,
                'cancellation_reason' => $validated['cancellation_reason'] ?? $cancellation->cancellation_reason,
                'pr_po_id' => $validated['pr_po_id'], // Explicitly update pr_po_id
                'cancellation_by' => auth()->id(), // Automatically set the authenticated user
            ]);

            if (isset($validated['items'])) {
                // Get the IDs of the items in the request
                $updatedItemIds = collect($validated['items'])->pluck('id')->filter()->toArray();
            
                // Remove items from the database that are not in the updated list
                $cancellation->items()->whereNotIn('id', $updatedItemIds)->delete();
            
                foreach ($validated['items'] as $item) {
                    $itemId = $item['id'] ?? null;
            
                    if ($itemId) {
                        // Update the existing item
                        $existingItem = $cancellation->items()->find($itemId);
                        if ($existingItem) {
                            $existingItem->update($item);
                        }
                    } else {
                        // Create a new item if it doesn't exist
                        $item['cancellation_id'] = $cancellation->id;
                        $item['cancellation_by'] = auth()->id();
                        CancellationItems::create($item);
                    }
                }
            } else {
                // If no items are provided, delete all items associated with the cancellation
                $cancellation->items()->delete();
            }

            \Log::info('Cancellation updated successfully:', $cancellation->toArray()); // Log the updated cancellation

            return response()->json([
                'cancellation' => $cancellation->load('items', 'user'),
                'message' => 'Cancellation updated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in update method:', ['errors' => $e->errors()]); // Log validation errors
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error in update method:', ['error' => $e->getMessage()]); // Log unexpected errors
            return response()->json(['message' => 'Failed to update cancellation.'], 500);
        }
    }

    // Remove the specified cancellation
    public function destroy($id)
    {
        $cancellation = Cancellation::findOrFail($id);
        $cancellation->delete();
        // return redirect()->route('cancellations.index')->with('success', 'Cancellation deleted successfully.');
    }
}
