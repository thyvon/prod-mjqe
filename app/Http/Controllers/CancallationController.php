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
        $prItems = PrItem::with(['product:id,product_description,sku', 'purchaseRequest:id,pr_number,request_by'])
            ->whereHas('purchaseRequest', function ($query) {
                $query->where('request_by', auth()->id()); // Filter by the authenticated user in purchaseRequest
            })
            ->when($request->query('pr_number'), function ($query, $prNumber) {
                $query->whereHas('purchaseRequest', function ($subQuery) use ($prNumber) {
                    $subQuery->where('pr_number', 'LIKE', "%{$prNumber}%"); // Use LIKE for partial matches
                });
            })
            ->get();

        return response()->json($prItems); // Return filtered PR items
    }

    public function getPoItems(Request $request)
    {
        $poItems = PoItems::with(['product:id,product_description,sku', 'purchaseOrder:id,po_number,purchased_by','purchaseRequest:id,pr_number'])
            ->whereHas('purchaseOrder', function ($query) {
                $query->where('purchased_by', auth()->id()); // Ensure this matches the database column
            })
            ->when($request->query('po_number'), function ($query, $poNumber) {
                $query->whereHas('purchaseOrder', function ($subQuery) use ($poNumber) {
                    $subQuery->where('po_number', 'LIKE', "%{$poNumber}%"); // Use LIKE for partial matches
                });
            })
            ->get();

        return response()->json($poItems);
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
                'items' => 'required|array',
                'items.*.purchase_order_id' => 'nullable|exists:purchase_orders,id',
                'items.*.purchase_order_item_id' => 'nullable|exists:po_items,id',
                'items.*.purchase_request_id' => 'nullable|exists:purchase_requests,id',
                'items.*.purchase_request_item_id' => 'nullable|exists:pr_items,id',
                'items.*.cancellation_reason' => 'nullable|string',
                'items.*.qty' => 'required', // Added validation for qty
            ]);

            \Log::info('Validated data:', $validated); // Log the validated data

            $validated['cancellation_by'] = auth()->id();
            $validated['cancellation_no'] = Cancellation::generateCancellationNo();

            $cancellation = Cancellation::create($validated);

            if (isset($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $item['cancellation_id'] = $cancellation->id;
                    $item['cancellation_by'] = $validated['cancellation_by'];
                    $item['purchase_order_id'] = $item['purchase_order_id'] ?? null;
                    $item['purchase_order_item_id'] = $item['purchase_order_item_id'] ?? null;
                    CancellationItems::create($item); // Save qty along with other fields
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

        $cancellation = Cancellation::with([
            'items.purchaseRequestItem.product',
            'items.purchaseRequestItem.purchaseRequest',
        ])->findOrFail($id);

        \Log::info('Cancellation data loaded for edit:', $cancellation->toArray()); // Log the loaded cancellation data

        return response()->json([
            'cancellation' => $cancellation, // Return the cancellation with related data
        ]);
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
                'items' => 'sometimes|array',
                'items.*.purchase_order_id' => 'nullable|exists:purchase_orders,id',
                'items.*.purchase_order_item_id' => 'nullable|exists:po_items,id',
                'items.*.purchase_request_id' => 'nullable|exists:purchase_requests,id',
                'items.*.purchase_request_item_id' => 'nullable|exists:pr_items,id',
                'items.*.cancellation_reason' => 'nullable|string',
                'items.*.qty' => 'required', // Added validation for qty
            ]);

            \Log::info('Validated data:', $validated); // Log the validated data

            $cancellation = Cancellation::findOrFail($id);

            $cancellation->update([
                'cancellation_date' => $validated['cancellation_date'] ?? $cancellation->cancellation_date,
                'cancellation_docs' => $validated['cancellation_docs'] ?? $cancellation->cancellation_docs,
                'cancellation_reason' => $validated['cancellation_reason'] ?? $cancellation->cancellation_reason,
            ]);

            if (isset($validated['items'])) {
                $existingItemIds = collect($validated['items'])->pluck('purchase_request_item_id')->toArray();
                $cancellation->items()->whereNotIn('purchase_request_item_id', $existingItemIds)->delete();

                foreach ($validated['items'] as $item) {
                    $existingItem = $cancellation->items()->where('purchase_request_item_id', $item['purchase_request_item_id'])->first();

                    if ($existingItem) {
                        $existingItem->update($item); // Update qty along with other fields
                    } else {
                        $item['cancellation_id'] = $cancellation->id;
                        $item['cancellation_by'] = auth()->id();
                        CancellationItems::create($item); // Save qty along with other fields
                    }
                }
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
