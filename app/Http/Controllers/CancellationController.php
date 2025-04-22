<?php

namespace App\Http\Controllers;

use App\Models\Cancellation;
use App\Models\CancellationItems;
use App\Models\PrItem;
use App\Models\PoItems;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CancellationController extends Controller
{
    // Display a listing of cancellations
    public function index()
    {
        $cancellations = Cancellation::with('items', 'user:id,name', 'purchaseRequest:id,pr_number')->latest()->get(); // Ensure latest data is fetched
        $users = User::select('id', 'name')->get(); // Fetch all users for dropdown
        return Inertia::render('Cancellations/Index', [
            'cancellations' => $cancellations,
            'users' => $users,
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
                'approved_by' => 'required|integer',
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

            $this->storeApprovals($cancellation->id, $request);  // Store approvals for the cancellation

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
        $cancellation = Cancellation::with('items.purchaseRequest','items.purchaseRequestItem','items.purchaseRequestItem.product', 'user:id,name,card_id,position,phone,signature', 'purchaseRequest:id,pr_number')->findOrFail($id);
        $approvals = Approval::where('approval_id', $id)
        ->whereIn('docs_type', [6]) // Filter by docs_type for ClearInvoice
        ->with('user:id,name,position,card_id,signature') // Include 'signature' field
        ->get()
        // Map approvals to include only necessary fields
        ->map(function ($approval) {
            $labels = [
                3 => 'Approved By',
            ];

            return [
                'label' => $labels[$approval->status_type] ?? 'Unknown',
                'user_id' => $approval->user_id, // Include user_id for button logic
                'name' => $approval->user->name ?? '',
                'position' => $approval->user->position ?? '',
                'card_id' => $approval->user->card_id ?? '',
                'signature' => $approval->user->signature ?? null,
                'status_type' => $approval->status_type, // Include status_type for button logic
                'status' => $approval->status, // Include status for button logic
                'click_date' => $approval->click_date, // Include click_date
            ];
        })
        ->values();
        \Log::info('Approvals retrieved:', $approvals->toArray());
        return Inertia::render('Cancellations/Show', [
            'cancellation' => $cancellation,
            'approvals' => $approvals,	
            'currentUser' => [
                'id' => Auth::id(), // Pass the authenticated user's ID
                'user' => Auth::user(), // Pass the authenticated user
            ],
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
            // $cancellation->items = $cancellation->items->map(function ($item) {
            //     return [
            //         'id' => $item->id,
            //         'name' => $item->purchaseRequestItem?->product?->product_description 
            //                   ?? $item->purchaseOrderItem?->product?->product_description,
            //         'pr_number' => $item->purchaseRequestItem?->purchaseRequest?->pr_number 
            //                        ?? $item->purchaseOrderItem?->purchaseRequest?->pr_number,
            //         'po_number' => $item->purchaseOrderItem?->purchaseOrder?->po_number,
            //         'sku' => $item->purchaseRequestItem?->product?->sku 
            //                  ?? $item->purchaseOrderItem?->product?->sku,
            //         'qty' => $item->qty,
            //         'purchase_request_id' => $item->purchase_request_id,
            //         'purchase_request_item_id' => $item->purchase_request_item_id,
            //         'purchase_order_id' => $item->purchase_order_id,
            //         'purchase_order_item_id' => $item->purchase_order_item_id,
            //         'cancellation_reason' => $item->cancellation_reason,
            //     ];
            // });

            // Retrieve approvals for the cancellation
            $approvals = Approval::where('approval_id', $id)
            ->whereIn('docs_type', [6]) // Filter by docs_type for ClearInvoice
            ->with('user:id,name,position,card_id,signature') // Include 'signature' field
            ->get()
            ->map(function ($approval) {
                $labels = [
                    3 => 'Approved By',
                ];

                return [
                    'label' => $labels[$approval->status_type] ?? 'Unknown',
                    'user_id' => $approval->user_id, // Include user_id for button logic
                    'name' => $approval->user->name ?? '',
                    'position' => $approval->user->position ?? '',
                    'card_id' => $approval->user->card_id ?? '',
                    'signature' => $approval->user->signature ?? null,
                    'status_type' => $approval->status_type, // Include status_type for button logic
                    'status' => $approval->status, // Include status for button logic
                    'click_date' => $approval->click_date, // Include click_date
                ];
            });

            $users = User::select('id', 'name')->get();
            
            \Log::info('Approvals:', $approvals->toArray());
            // Explicitly include pr_po_id and cancellation_docs in the response
            $response = [
                'cancellation' => $cancellation,
                'pr_po_id' => $cancellation->pr_po_id,
                'cancellation_docs' => $cancellation->cancellation_docs,
                'cancellation_date' => $cancellation->cancellation_date,
                'cancellation_reason' => $cancellation->cancellation_reason,	
                'approvals' => $approvals,
                'users' => $users, 
                'currentUser' => [
                    'id' => Auth::id(), // Pass the authenticated user's ID
                    'user' => Auth::user(), // Pass the authenticated user
                ],
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
                'approved_by' => 'required|integer',
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
                'pr_po_id' => $cancellation->pr_po_id, // Explicitly update pr_po_id
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

            $this->updateApprovals($cancellation->id, $validated['approved_by'], 6); // Pass docsType as 6

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
        try {
            // Find the cancellation record
            $cancellation = Cancellation::findOrFail($id);
    
            // Update the quantity of related items to 0 and save
            foreach ($cancellation->items as $item) {
                $item->update(['qty' => 0]); // Set qty to 0
            }
    
            // Recalculate quantities for related PrItems
            foreach ($cancellation->items as $item) {
                $prItem = $item->purchaseRequestItem;
                if ($prItem) {
                    $prItem->recalculateQtyCancel();
                }
            }
    
            // Delete related approvals
            Approval::where('approval_id', $cancellation->id)->delete();
    
            // Delete the cancellation record
            $cancellation->delete();
    
            return response()->json(['message' => 'Cancellation deleted successfully.']);
        } catch (\Exception $e) {
            \Log::error('Error in destroy method:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete cancellation.'], 500);
        }
    }
    // Store approvals for the cancellation
    private function storeApprovals($cancelId, $request)
    {
        $docsType = 6;
    
        if ($request->approved_by) {
            $this->storeNewApprovals($cancelId, $request->approved_by, $docsType);
        }
    }
    
    private function storeNewApprovals($cancelId, $userId, $docsType)
    {
        Approval::create([
            'approval_id' => $cancelId,
            'status_type' => 3, // Approved By
            'docs_type' => $docsType,
            'user_id' => $userId,
            'approval_name' => 'Request for Cancel PR',
        ]);
    }
    
    private function updateApprovals($cancelId, $userId, $docsType)
    {
        $approval = Approval::where('approval_id', $cancelId)
            ->where('status_type', 3) // Approved By
            ->where('docs_type', $docsType)
            ->first();
    
        if ($approval) {
            $approval->update([
                'user_id' => $userId,
                'docs_type' => $docsType,
                'approval_name' => 'Request for Cancel PR',
            ]);
        }
    }

    // public function approve(Request $request, $id)
    // {
    //     try {
    //         $request->validate([
    //             'status_type' => 'required|integer',
    //         ]);

    //         $currentUser = Auth::user();

    //         // Determine docs_type based on clear_type
    //         $cancellation = Cancellation::findOrFail($id);
    //         $docsType = 6;

    //         // Find or create the approval record for the current user, status type, and docs_type
    //         $approval = Approval::firstOrCreate(
    //             [
    //                 'approval_id' => $id,
    //                 'status_type' => $request->status_type,
    //                 'user_id' => $currentUser->id,
    //                 'docs_type' => $docsType,
    //             ],
    //             [
    //                 'approval_name' => 'Request for Cancel PR',
    //                 'status' => 0, // Default status
    //             ]
    //         );

    //         // Update the approval status
    //         $approval->update([
    //             'status' => 1, // Update the status to 'approved'
    //             'click_date' => now(), // Capture the current date
    //         ]);

    //         if ($request->status_type == 1) {
    //             $cancellation->status = 1; // Checked
    //         } elseif ($request->status_type == 3) {
    //             $cancellation->status = 3; // Approved
    //         // Recalculate quantities for related PrItems
    //         foreach ($cancellation->items as $item) {
    //             $prItem = $item->purchaseRequestItem;
    //             if ($prItem) {
    //                 $prItem->recalculateQtyCancel();
    //             }
    //         }
    //         }
    //         $cancellation->save();

    //         return response()->json(['message' => 'Approval successful.']);
    //     } catch (\Exception $e) {
    //         \Log::error('Approval Error:', [
    //             'error' => $e->getMessage(),
    //             'stack' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json(['message' => 'An error occurred while processing the approval.'], 500);
    //     }
    // }

    public function approve(Request $request, $id)
    {
        try {
            $request->validate([
                'status_type' => 'required|integer',
            ]);

            $currentUser = Auth::user();

            // Start a database transaction
            \DB::beginTransaction();

            // Determine docs_type based on clear_type
            $cancellation = Cancellation::findOrFail($id);
            $docsType = 6;

            // Find or create the approval record for the current user, status type, and docs_type
            $approval = Approval::firstOrCreate(
                [
                    'approval_id' => $id,
                    'status_type' => $request->status_type,
                    'user_id' => $currentUser->id,
                    'docs_type' => $docsType,
                ],
                [
                    'approval_name' => 'Request for Cancel PR',
                    'status' => 0, // Default status
                ]
            );

            // Update the approval status
            $approval->update([
                'status' => 1, // Update the status to 'approved'
                'click_date' => now(), // Capture the current date
            ]);

            if ($request->status_type == 1) {
                $cancellation->status = 1; // Checked
            } elseif ($request->status_type == 3) {
                $cancellation->status = 3; // Approved

                // Recalculate quantities for related PrItems
                foreach ($cancellation->items as $item) {
                    $prItem = $item->purchaseRequestItem;
                    if ($prItem) {
                        $prItem->recalculateQtyCancel();
                    }
                }
            }

            // Save the cancellation
            $cancellation->save();

            // Commit the transaction
            \DB::commit();

            return response()->json(['message' => 'Approval successful.']);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            \DB::rollBack();

            \Log::error('Approval Error:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'An error occurred while processing the approval.'], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'status_type' => 'required|integer',
            ]);

            $currentUser = Auth::user();

            // Find the approval record for the current user and status type
            $approval = Approval::where('approval_id', $id)
                ->where('status_type', $request->status_type)
                ->where('user_id', $currentUser->id)
                ->first();

            if (!$approval) {
                \Log::warning('Approval record not found or unauthorized.', [
                    'clearInvoiceId' => $id,
                    'statusType' => $request->status_type,
                    'userId' => $currentUser->id,
                ]);
                return response()->json(['message' => 'Approval record not found or unauthorized.'], 403);
            }

            // Update the approval status to rejected
            $approval->update([
                'status' => -1, // Set status to -1 for rejection
                'click_date' => now(), // Capture the current date
            ]);

            $cancellation = Cancellation::findOrFail($id);
            $cancellation->status = -1; // Rejected
            $cancellation->save();

            return response()->json(['message' => 'Rejection successful.']);
        } catch (\Exception $e) {
            \Log::error('Rejection Error:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred while processing the rejection.'], 500);
        }
    }
}
