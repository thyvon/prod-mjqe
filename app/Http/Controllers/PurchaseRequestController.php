<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\PrItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseRequestController extends Controller
{
    // Display a list of purchase requests
    public function index()
    {
        $purchaseRequests = PurchaseRequest::with(['prItems.product', 'requestBy'])->get();
        $currentUser = auth()->user();
        $products = Product::all(); // Fetch all products

        return Inertia::render('Purchase/Pr/Index', [
            'purchaseRequests' => $purchaseRequests,
            'currentUser' => $currentUser,
            'products' => $products, // Pass products to the frontend
        ]);
    }

    // Store a newly created purchase request with items
    // public function store(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'request_date' => 'required|date',
    //             'request_by' => 'required|exists:users,id', // Validate as user ID
    //             'campus' => 'required|string',
    //             'division' => 'required|string',
    //             'department' => 'required|string',
    //             'purpose' => 'nullable|string',
    //             'is_urgent' => 'boolean',
    //             'items' => 'required|array',
    //             'items.*.product_id' => 'required|exists:products,id',
    //             'items.*.remark' => 'nullable|string',
    //             'items.*.qty' => 'required|numeric|min:1',
    //             'items.*.uom' => 'required|string',
    //             'items.*.price' => 'required|numeric|min:0',
    //             'items.*.total_price' => 'required|numeric|min:0', // Correct field reference for item total price
    //             'items.*.campus' => 'required|string',
    //             'items.*.division' => 'required|string',
    //             'items.*.department' => 'required|string',
    //         ]);

    //         // Auto-generate PR number
    //         $validated['pr_number'] = 'PR-' . strtoupper(uniqid());

    //         // Calculate total amount and total item count for the purchase request
    //         $validated['total_amount'] = collect($validated['items'])->sum('total_price');
    //         $validated['total_item'] = count($validated['items']);

    //         // Set default status to Pending
    //         $validated['status'] = 'Pending';

    //         // Ensure is_urgent is correctly set as boolean
    //         $validated['is_urgent'] = $request->input('is_urgent') ? 1 : 0;

    //         $purchaseRequest = PurchaseRequest::create($validated);

    //         // Create PR Items
    //         foreach ($validated['items'] as $item) {
    //             $item['qty_last'] = $item['qty']; // Initialize qty_last with the requested qty
    //             $purchaseRequest->prItems()->create($item);
    //         }

    //         $purchaseRequest->load(['prItems.product', 'requestBy']); // Load the requestBy relationship

    //         return response()->json($purchaseRequest, 201);
    //     } catch (ValidationException $e) {
    //         return response()->json(['errors' => $e->errors()], 422); // Return validation errors
    //     } catch (\Exception $e) {
    //         \Log::error('Error creating purchase request: ' . $e->getMessage()); // Log the error
    //         \Log::error('Request data: ' . json_encode($request->all())); // Log the request data for more details
    //         \Log::error('Stack trace: ' . $e->getTraceAsString()); // Log the stack trace for more details
    //         return response()->json(['error' => 'Error creating purchase request.'], 500);
    //     }
    // }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_date' => 'required|date',
                'request_by' => 'required|exists:users,id', // Validate as user ID
                'campus' => 'required|string',
                'division' => 'required|string',
                'department' => 'required|string',
                'purpose' => 'nullable|string',
                'is_urgent' => 'boolean',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.remark' => 'nullable|string',
                'items.*.qty' => 'required|numeric|min:1',
                'items.*.uom' => 'required|string',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.total_price' => 'required|numeric|min:0', // Correct field reference for item total price
                'items.*.campus' => 'required|string',
                'items.*.division' => 'required|string',
                'items.*.department' => 'required|string',
            ]);
    
            // Generate PR number in the format "PR-CEN-20250106-001"
            $date = now()->format('Ymd'); // Get current date in yyyymmdd format
            $campus = strtoupper($validated['campus']); // Capitalize campus code, assuming CEN for your example
            $count = PurchaseRequest::whereDate('created_at', today())
                                   ->where('campus', $validated['campus'])
                                   ->count() + 1; // Get today's count of requests for this campus
            $pr_number = "PR-{$campus}-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT); // Format count with leading zeros
    
            // Ensure PR number is unique
            while (PurchaseRequest::where('pr_number', $pr_number)->exists()) {
                $count++;
                $pr_number = "PR-{$campus}-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
    
            $validated['pr_number'] = $pr_number;
    
            // Calculate total amount and total item count for the purchase request
            $validated['total_amount'] = collect($validated['items'])->sum('total_price');
            $validated['total_item'] = count($validated['items']);
    
            // Set default status to Pending
            $validated['status'] = 'Pending';
    
            // Ensure is_urgent is correctly set as boolean
            $validated['is_urgent'] = $request->input('is_urgent') ? 1 : 0;
    
            $purchaseRequest = PurchaseRequest::create($validated);
    
            // Create PR Items
            foreach ($validated['items'] as $item) {
                $item['qty_last'] = $item['qty']; // Initialize qty_last with the requested qty
                $purchaseRequest->prItems()->create($item);
            }
    
            $purchaseRequest->load(['prItems.product', 'requestBy']); // Load the requestBy relationship
    
            return response()->json($purchaseRequest, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422); // Return validation errors
        } catch (\Exception $e) {
            \Log::error('Error creating purchase request: ' . $e->getMessage()); // Log the error
            \Log::error('Request data: ' . json_encode($request->all())); // Log the request data for more details
            \Log::error('Stack trace: ' . $e->getTraceAsString()); // Log the stack trace for more details
            return response()->json(['error' => 'Error creating purchase request.'], 500);
        }
    }
    


    // Show the form to edit an existing purchase request
    public function edit($id)
    {
        try {
            \Log::info('Edit method called with ID:', ['id' => $id]); // Log the ID
            $purchaseRequest = PurchaseRequest::with(['prItems.product', 'requestBy'])->findOrFail($id); // Ensure requestBy relationship is loaded
            \Log::info('Editing Purchase Request:', ['purchaseRequest' => $purchaseRequest->toArray()]); // Log the purchase request data
            return response()->json($purchaseRequest); // Return the purchase request as JSON
        } catch (\Exception $e) {
            \Log::error('Error in edit method:', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error fetching purchase request.'], 500);
        }
    }

    public function show($id)
    {
        // Fetch the Purchase Request by ID, including its related PR items
        $purchaseRequest = PurchaseRequest::with('prItems.product')->findOrFail($id);

        // Get all available products to display in the frontend
        $products = Product::all();

        // Get all users to display in the frontend
        $users = User::all();

        // Return the view with the PR data, associated products, and users
        return Inertia::render('Purchase/Pr/Show', [
            'purchaseRequest' => $purchaseRequest,
            'products' => $products,
            'users' => $users
        ]);
    }

    // Update an existing purchase request with items
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'pr_number' => [
                    'required',
                    'string',
                    Rule::unique('purchase_requests', 'pr_number')->ignore($id),
                ],
                'request_date' => 'required|date',
                'request_by' => 'required|exists:users,id', // Validate as user ID
                'campus' => 'required|string',
                'division' => 'required|string',
                'department' => 'required|string',
                'purpose' => 'nullable|string',
                'is_urgent' => 'boolean',
                'items' => 'required|array',
                'items.*.id' => 'nullable|exists:pr_items,id',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.remark' => 'nullable|string',
                'items.*.qty' => 'required|numeric|min:1',
                'items.*.uom' => 'required|string',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.total_price' => 'required|numeric|min:0', // Correct field reference for item total price
                'items.*.campus' => 'required|string',
                'items.*.division' => 'required|string',
                'items.*.department' => 'required|string',
            ]);

            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->update($validated);

            // Sync or update PR Items
            $existingItemIds = collect($validated['items'])->pluck('id')->filter();
            $purchaseRequest->prItems()->whereNotIn('id', $existingItemIds)->delete(); // Delete removed items
            foreach ($validated['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    $item = PrItem::find($itemData['id']);
                    $item->update($itemData);
                    $item->qty_last = $item->qty - $item->qty_cancel; // Update qty_last
                    if ($item->is_cancel) {
                        $item->status = 'Cancelled';
                    }
                    $item->save();
                } else {
                    $itemData['qty_last'] = $itemData['qty']; // Initialize qty_last with the requested qty
                    $purchaseRequest->prItems()->create($itemData);
                }
            }

            // Recalculate total amount and total item count for the purchase request
            $purchaseRequest->total_amount = $purchaseRequest->prItems->sum('total_price');
            $purchaseRequest->total_item = $purchaseRequest->prItems->count();
            $purchaseRequest->is_urgent = $request->input('is_urgent') ? 1 : 0; // Ensure is_urgent is updated
            $purchaseRequest->save();

            $purchaseRequest->load(['prItems.product', 'requestBy']); // Load the requestBy relationship

            return response()->json($purchaseRequest, 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422); // Return validation errors
        } catch (\Exception $e) {
            \Log::error('Error updating purchase request: ' . $e->getMessage()); // Log the error
            \Log::error('Request data: ' . json_encode($request->all())); // Log the request data for more details
            \Log::error('Stack trace: ' . $e->getTraceAsString()); // Log the stack trace for more details
            return response()->json(['error' => 'Error updating purchase request.'], 500);
        }
    }

    // Cancel a purchase request
    public function cancel($id)
    {
        try {
            $purchaseRequest = PurchaseRequest::with('prItems.product', 'requestBy')->findOrFail($id);
            $purchaseRequest->is_cancel = 1;
            $purchaseRequest->status = 'Cancelled';
            $purchaseRequest->save();

            // Update qty_cancel and qty_last for each item
            foreach ($purchaseRequest->prItems as $item) {
                $item->qty_cancel = $item->qty;
                $item->qty_last = 0;
                if ($item->qty_cancel == $item->qty) {
                    $item->is_cancel = 1;
                    $item->status = 'Cancelled';
                }
                $item->save();
            }

            $purchaseRequest->load(['prItems.product', 'requestBy']); // Ensure requestBy relationship is loaded

            return response()->json($purchaseRequest); // Return the updated purchase request
        } catch (\Exception $e) {
            \Log::error('Error canceling purchase request: ' . $e->getMessage()); // Log the error
            return response()->json(['error' => 'Error canceling purchase request.'], 500);
        }
    }

    // Cancel specific items in a purchase request
    public function cancelItems(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|exists:pr_items,id',
                'items.*.qty_cancel' => 'required|numeric|min:0',
                'items.*.reason' => 'required|string|max:255',
            ]);

            $purchaseRequest = PurchaseRequest::findOrFail($id);

            foreach ($validated['items'] as $itemData) {
                $item = PrItem::findOrFail($itemData['id']);
                $item->qty_cancel += $itemData['qty_cancel'];
                $item->reason = $itemData['reason'];
                $item->qty_last = $item->qty - $item->qty_cancel;
                if ($item->qty_cancel == $item->qty) {
                    $item->is_cancel = 1;
                    $item->status = 'Cancelled';
                }
                $item->save();
            }

            // Check if all items are canceled
            $allItemsCanceled = $purchaseRequest->prItems->every(function ($item) {
                return $item->qty_cancel == $item->qty;
            });

            if ($allItemsCanceled) {
                $purchaseRequest->status = 'Cancelled';
                $purchaseRequest->is_cancel = 1;
                $purchaseRequest->save();
            }

            $purchaseRequest->load(['prItems.product', 'requestBy']); // Ensure requestBy relationship is loaded

            return response()->json($purchaseRequest); // Return the updated purchase request
        } catch (ValidationException $e) {
            \Log::error('Validation error canceling items: ' . json_encode($e->errors())); // Log validation errors
            return response()->json(['errors' => $e->errors()], 422); // Return validation errors
        } catch (\Exception $e) {
            \Log::error('Error canceling items: ' . $e->getMessage()); // Log the error
            \Log::error('Request data: ' . json_encode($request->all())); // Log the request data for more details
            \Log::error('Stack trace: ' . $e->getTraceAsString()); // Log the stack trace for more details
            return response()->json(['error' => 'Error canceling items.'], 500);
        }
    }

    // Delete a purchase request and its items
    public function destroy($id)
    {
        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->prItems()->delete(); // Delete associated items
            $purchaseRequest->delete();

            return response()->json(['success' => 'Purchase request deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting purchase request.'], 500);
        }
    }
}
