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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CancellationController extends Controller
{
    // Display a listing of cancellations
    public function index(Request $request)
    {
        // Initialize the query for cancellations
        $cancellationsQuery = Cancellation::with('items', 'user:id,name', 'purchaseRequest:id,pr_number', 'purchaseOrder:id,po_number')->latest();
    
        // Check if a cancellation_docs filter is applied from the query string
        if ($request->has('cancellations_docs') && in_array($request->cancellations_docs, [1, 2])) {
            $cancellationsQuery->where('cancellation_docs', $request->cancellations_docs);
        }
    
        // Fetch filtered cancellations
        $cancellations = $cancellationsQuery->get();
    
        // Fetch all users for the dropdown
        $users = User::select('id', 'name')->get();
    
        return Inertia::render('Cancellations/Index', [
            'cancellations' => $cancellations,
            'users' => $users,
        ]);
    }
    

    // Ensure the getPrItems method is accessible via an API route
    public function getPrItems(Request $request)
    {
        $prItems = PrItem::with(['product:id,product_description,sku', 'purchaseRequest:id,pr_number'])
            // ->whereHas('purchaseRequest', function ($query) {
            //     $query->where('request_by', auth()->id());
            // })
            ->where('qty_pending', '!=', 0) // Ensure only items with pending quantity are fetched
            ->get();

        return response()->json($prItems); // Ensure PR numbers are included
    }

    public function getPoItems(Request $request)
    {
        $poItems = PoItems::with(['product:id,product_description,sku', 'purchaseOrder:id,po_number','purchaseRequest:id,pr_number'])
            // ->whereHas('purchaseOrder', function ($query) {
            //     $query->where('purchased_by', auth()->id());
            // })
            ->where('pending', '!=', 0) // Ensure only items with pending quantity are fetche
            ->get();

        return response()->json($poItems); // Ensure PO numbers are included
    }

    // Store a newly created cancellation
    public function store(Request $request)
    {
        \Log::info('Store method called with data:', $request->all()); // Log the incoming request data

        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'cancellation_date' => 'required|date',
                'cancellation_docs' => 'required|integer',
                'cancellation_reason' => 'nullable|string',
                'pr_po_id' => 'nullable|integer', // Add validation for pr_po_id
                'approved_by' => 'required|integer',
                'authorized_by' => 'nullable|integer',
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
                    $item['purchase_request_id'] = $item['purchase_request_id'] ?? null;
                    $item['purchase_request_item_id'] = $item['purchase_request_item_id'] ?? null;


                    Log::info('Creating cancellation item:', $item);

                    CancellationItems::create($item); // Save cancellation_reason along with other fields
                    // Recalculate logic
                    if ($validated['cancellation_docs'] == 1 && isset($item['purchase_request_item_id'])) {
                        PrItem::find($item['purchase_request_item_id'])?->recalculateQtyCancelValidation();
                    }

                    if ($validated['cancellation_docs'] == 2 && isset($item['purchase_order_item_id'])) {
                        PoItems::find($item['purchase_order_item_id'])?->recalculateQtyCancelValidation();
                    }
                }
            }

            $this->storeApprovals($cancellation->id, $request);  // Store approvals for the cancellation

            DB::commit(); // Commit the transaction

            \Log::info('Cancellation created successfully:', $cancellation->toArray()); // Log the created cancellation

            return response()->json([
                'cancellation' => $cancellation->load('items', 'user'),
                'message' => 'Cancellation created successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in store method:', ['errors' => $e->errors()]); // Log validation errors
            DB::rollBack(); // Rollback the transaction on validation error
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error in store method:', ['error' => $e->getMessage()]); // Log unexpected errors
            DB::rollBack(); // Rollback the transaction on unexpected error
            return response()->json(['message' => 'Failed to create cancellation.'], 500);
        }
    }

    // Display the specified cancellation
    public function show($id)
    {
        $cancellation = Cancellation::with(
            'items.purchaseRequest',
            'items.purchaseRequestItem',
            'items.purchaseRequestItem.product',
            'items.purchaseOrder',
            'items.purchaseOrderItem',
            'items.purchaseOrderItem.product',
            'user:id,name,card_id,position,phone,signature',
            'purchaseRequest:id,pr_number',
            'purchaseOrder:id,po_number'
        )->findOrFail($id);
    
        $approvals = Approval::where('approval_id', $id)
            ->whereIn('docs_type', [6, 7])
            ->with('user:id,name,position,card_id,signature')
            ->get()
            ->map(function ($approval) {
                $labels = [
                    3 => 'Approved By',
                    5 => 'Authorized By',
                ];
    
                return [
                    'label' => $labels[$approval->status_type] ?? 'Unknown',
                    'user_id' => $approval->user_id,
                    'name' => $approval->user->name ?? '',
                    'position' => $approval->user->position ?? '',
                    'card_id' => $approval->user->card_id ?? '',
                    'signature' => $approval->user->signature ?? null,
                    'status_type' => $approval->status_type,
                    'status' => $approval->status,
                    'click_date' => $approval->click_date,
                ];
            })
            ->values();
    
        \Log::info('Approvals retrieved:', $approvals->toArray());
    
        // Dynamic view based on integer cancellation_docs value
        $view = match ($cancellation->cancellation_docs) {
            1 => 'Cancellations/ShowPrCancel',
            2 => 'Cancellations/ShowPoCancel',
            default => 'Cancellations/ShowCancel',
        };

        Log::info('Rendering view:', ['view' => $view]);
    
        return Inertia::render($view, [
            'cancellation' => $cancellation,
            'approvals' => $approvals,
            'currentUser' => [
                'id' => Auth::id(),
                'user' => Auth::user(),
            ],
        ]);
    }
    // Show the form for creating a new cancellation    

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

            // Retrieve approvals for the cancellation
            $approvals = Approval::where('approval_id', $id)
            ->whereIn('docs_type', [6,7]) // Filter by docs_type for ClearInvoice
            ->with('user:id,name,position,card_id,signature') // Include 'signature' field
            ->get()
            ->map(function ($approval) {
                $labels = [
                    3 => 'Approved By',
                    5 => 'Authorized By',
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
        \Log::info('Update method called with data:', $request->all());
    
        DB::beginTransaction();
    
        try {
            $validated = $request->validate([
                'cancellation_date' => 'sometimes|date',
                'cancellation_docs' => 'sometimes|integer',
                'cancellation_reason' => 'nullable|string',
                'pr_po_id' => 'nullable|integer',
                'approved_by' => 'required|integer',
                'authorized_by' => 'nullable|integer',
                'items' => 'sometimes|array',
                'items.*.id' => 'required|exists:cancellation_items,id',
                'items.*.purchase_order_id' => 'nullable|exists:purchase_orders,id',
                'items.*.purchase_order_item_id' => 'nullable|exists:po_items,id',
                'items.*.purchase_request_id' => 'nullable|exists:purchase_requests,id',
                'items.*.purchase_request_item_id' => 'nullable|exists:pr_items,id',
                'items.*.cancellation_reason' => 'nullable|string',
                'items.*.qty' => 'required',
            ]);
    
            \Log::info('Validated data:', $validated);
    
            $cancellation = Cancellation::findOrFail($id);
    
            $cancellation->update([
                'cancellation_date' => $validated['cancellation_date'] ?? $cancellation->cancellation_date,
                'cancellation_docs' => $validated['cancellation_docs'] ?? $cancellation->cancellation_docs,
                'cancellation_reason' => $validated['cancellation_reason'] ?? $cancellation->cancellation_reason,
                'pr_po_id' => $cancellation->pr_po_id,
                'cancellation_by' => auth()->id(),
            ]);
    
            if (isset($validated['items'])) {
                $updatedItemIds = collect($validated['items'])->pluck('id')->filter()->toArray();
                $cancellation->items()->whereNotIn('id', $updatedItemIds)->delete();
    
                foreach ($validated['items'] as $item) {
                    $itemId = $item['id'] ?? null;
                
                    if ($itemId) {
                        $existingItem = $cancellation->items()->find($itemId);
                        if ($existingItem) {
                            $existingItem->update($item);
                        }
                    } else {
                        $item['cancellation_id'] = $cancellation->id;
                        $item['cancellation_by'] = auth()->id();
                        $cancellation->items()->create($item);
                    }
                
                    // Recalculate after update or create
                    if ($validated['cancellation_docs'] == 1 && isset($item['purchase_request_item_id'])) {
                        PrItem::find($item['purchase_request_item_id'])?->recalculateQtyCancelValidation();
                    }
                
                    if ($validated['cancellation_docs'] == 2 && isset($item['purchase_order_item_id'])) {
                        PoItems::find($item['purchase_order_item_id'])?->recalculateQtyCancelValidation();
                    }
                }
            } else {
                $cancellation->items()->delete();
            }
    
            $docsType = match ((int) $validated['cancellation_docs']) {
                1 => 6, // PR
                2 => 7, // PO
                default => 0,
            };
            $this->updateApprovals($cancellation->id, $validated['approved_by'], $validated['authorized_by'], $docsType);
            Log::info('Approvals updated:', [
                'approved_by' => $validated['approved_by'],
                'authorized_by' => $validated['authorized_by'],
            ]);
    
            DB::commit();
    
            \Log::info('Cancellation updated successfully:', $cancellation->toArray());
    
            return response()->json([
                'cancellation' => $cancellation->load('items', 'user'),
                'message' => 'Cancellation updated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Validation error in update method:', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in update method:', ['error' => $e->getMessage()]);
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

            $docsType = match ($cancellation->cancellation_docs) {
                1 => 6, // If cancellation_docs = 1, docs_type = 6
                2 => 7, // If cancellation_docs = 2, docs_type = 7
                default => null, // Handle unexpected values
            };

            if (!$docsType) {
                return response()->json(['message' => 'Invalid cancellation_docs value.'], 400);
            }
    
            // Recalculate quantities for related PrItems
            foreach ($cancellation->items as $item) {
                if ($cancellation->cancellation_docs == 1 && $item->purchaseRequestItem) {
                    $item->purchaseRequestItem->recalculateQtyCancel();
                }
            
                if ($cancellation->cancellation_docs == 2 && $item->purchaseOrderItem) {
                    $item->purchaseOrderItem->recalculateQtyCancel();
                }
            }  
    
            Approval::where([
                'approval_id' => $cancellation->id,
                'docs_type' => $docsType, // Filter by dynamically set docs_type
            ])->delete();
    
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
        $docsType = match ((int) $request->cancellation_docs) {
            1 => 6, // PR Cancellation
            2 => 7, // PO Cancellation
            default => 0,
        };
    
        if ($request->approved_by) {
            $this->storeNewApprovals($cancelId, $request->approved_by, $request->authorized_by, $docsType);
        }
    }
    
    
    private function storeNewApprovals($cancelId, $approvedBy, $authorizedBy, $docsType)
    {
        $docLabel = match ($docsType) {
            6 => 'PR Cancellation',
            7 => 'PO Cancellation',
            default => 'Document',
        };
        // Approved By
        Approval::create([
            'approval_id' => $cancelId,
            'status_type' => 3,
            'docs_type' => $docsType,
            'user_id' => $approvedBy,
            'approval_name' => "$docLabel",
        ]);
    
        // Authorized By (only if not null)
        if (!empty($authorizedBy)) {
            Approval::create([
                'approval_id' => $cancelId,
                'status_type' => 5,
                'docs_type' => $docsType,
                'user_id' => $authorizedBy,
                'approval_name' => "$docLabel",
            ]);
        }
    }
    
    private function updateApprovals($cancelId, $approvedBy, $authorizedBy, $docsType)
    {
        $docLabel = match ($docsType) {
            6 => 'PR Cancellation',
            7 => 'PO Cancellation',
            default => 'Document',
        };
    
        // -- Handle Approved By (status_type 3) --
        $approval = Approval::where('approval_id', $cancelId)
            ->where('status_type', 3)
            ->where('docs_type', $docsType)
            ->first();
    
        if ($approval) {
            $approval->update([
                'user_id' => $approvedBy,
                'approval_name' => "$docLabel",
            ]);
        } else {
            Approval::create([
                'approval_id' => $cancelId,
                'status_type' => 3,
                'docs_type' => $docsType,
                'user_id' => $approvedBy,
                'approval_name' => "$docLabel",
            ]);
        }
    
        // -- Handle Authorized By (status_type 5) --
        if (!empty($authorizedBy)) {
            $authorization = Approval::where('approval_id', $cancelId)
                ->where('status_type', 5)
                ->where('docs_type', $docsType)
                ->first();
    
            if ($authorization) {
                $authorization->update([
                    'user_id' => $authorizedBy,
                    'approval_name' => "$docLabel",
                ]);
            } else {
                Approval::create([
                    'approval_id' => $cancelId,
                    'status_type' => 5,
                    'docs_type' => $docsType,
                    'user_id' => $authorizedBy,
                    'approval_name' => "$docLabel",
                ]);
            }
        }
    }
    
    public function approve(Request $request, $id)
    {
        try {
            $request->validate([
                'status_type' => 'required|integer',
            ]);
    
            $currentUser = Auth::user();
    
            // Start a database transaction
            \DB::beginTransaction();
    
            // Fetch the cancellation record
            $cancellation = Cancellation::findOrFail($id);
    
            // Determine docs_type and approval_name based on cancellation_docs
            $docsType = match ((int) $cancellation->cancellation_docs) {
                1 => 6, // PR Cancellation
                2 => 7, // PO Cancellation
                default => 0,
            };
    
            $approval = Approval::where([
                'approval_id' => $id,
                'status_type' => $request->status_type,
                'user_id' => $currentUser->id,
                'docs_type' => $docsType,
            ])->first();
            
            if (!$approval) {
                return response()->json(['message' => 'Approval record not found or unauthorized.'], 403);
            }
            
            // Update the approval record
            $approval->update([
                'status' => 1,
                'click_date' => now(),
            ]);
    
            // Update cancellation status based on status_type
            if ($request->status_type == 1) {
                $cancellation->status = 1; // Checked
            } elseif ($request->status_type == 3) {
                $cancellation->status = 3; // Approved
                foreach ($cancellation->items as $item) {
                    if ($cancellation->cancellation_docs == 2 && $item->purchaseOrderItem) {
                        $item->purchaseOrderItem->recalculateQtyCancel();
                    }
                }
            } elseif ($request->status_type == 5) {
                $cancellation->status = 5; // Authorized
    
                // Recalculate quantities
                foreach ($cancellation->items as $item) {
                    if ($cancellation->cancellation_docs == 1 && $item->purchaseRequestItem) {
                        $item->purchaseRequestItem->recalculateQtyCancel();
                    }
                }
            }
    
            // Save the cancellation
            $cancellation->save();
    
            // Commit the transaction
            \DB::commit();
    
            return response()->json(['message' => 'Approval successful.']);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
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
    
            // Start a database transaction
            \DB::beginTransaction();
    
            // Fetch the cancellation record
            $cancellation = Cancellation::findOrFail($id);
    
            // Determine docs_type based on cancellation_docs
            $docsType = match ((int) $cancellation->cancellation_docs) {
                1 => 6, // PR
                2 => 7, // PO
                default => 0,
            };
    
            // Find the approval record for the current user and status type
            $approval = Approval::where('approval_id', $id)
                ->where('status_type', $request->status_type)
                ->where('user_id', $currentUser->id)
                ->where('docs_type', $docsType)
                ->first();
    
            if (!$approval) {
                \Log::warning('Approval record not found or unauthorized.', [
                    'cancellationId' => $id,
                    'statusType' => $request->status_type,
                    'userId' => $currentUser->id,
                    'docsType' => $docsType,
                ]);
                return response()->json(['message' => 'Approval record not found or unauthorized.'], 403);
            }
    
            // Update the approval status to rejected
            $approval->update([
                'status' => -1, // Rejected
                'click_date' => now(),
            ]);
    
            // Update cancellation status
            $cancellation->status = -1;
            $cancellation->save();
    
            // Commit the transaction
            \DB::commit();
    
            return response()->json(['message' => 'Rejection successful.']);
        } catch (\Exception $e) {
            \DB::rollBack();
    
            \Log::error('Rejection Error:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
    
            return response()->json(['message' => 'An error occurred while processing the rejection.'], 500);
        }
    }
    
}
