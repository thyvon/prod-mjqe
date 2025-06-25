<?php

namespace App\Http\Controllers;

use App\Models\ClearInvoice;
use App\Models\User;
use App\Models\CashRequest;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Approval; // Import Approval model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClearInvoiceController extends Controller
{
    public function index()
    {
        $clearInvoices = ClearInvoice::with([
            'cashRequest:id,ref_no',
            // 'cashRequest.purchaseInvoice:id,pi_number,currency,paid_amount',
            'user:id,name']) // Adjust relationships as needed
            ->select('id', 'ref_no', 'description', 'remark', 'clear_type', 'clear_by', 'status', 'clear_date', 'cash_id') // Ensure 'cash_id' is selected
            ->get();

        $cashRequests = CashRequest::with('user:id,name')->select('id', 'ref_no', 'request_type', 'status', 'user_id','request_date','amount')->get(); // Include 'request_type' in the selection
        $users = User::select('id', 'name')->get();
        $currentUser = auth()->user()->only(['id', 'name']); // Get only the user id and name

        return Inertia::render('ClearInvoice/Index', [
            'clearInvoices' => $clearInvoices,
            'cashRequests' => $cashRequests,
            'users' => $users,
            'currentUser' => (object) $currentUser, // Pass the current user as an object to the Vue component
        ]);
    }

    public function show($id)
    {
        try {
            $clearInvoice = ClearInvoice::with(['cashRequest.user:id,name', 'user:id,name,card_id,position,phone,signature']) // Include 'signature' field
                ->findOrFail($id);
    
            // Fetch approvals for the clear invoice
            $approvals = Approval::where('approval_id', $id)
                ->whereIn('docs_type', [3, 4]) // Filter by docs_type for ClearInvoice
                ->with('user:id,name,position,card_id,signature') // Include 'signature' field
                ->get()
                ->map(function ($approval) {
                    $labels = [
                        1 => 'Checked By',
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
                ->values(); // Reindex the collection
    
            $users = User::select('id', 'name')->get();
            $cashRequests = CashRequest::with('user:id,name')->select('id', 'ref_no', 'request_type', 'status', 'user_id', 'request_date', 'amount')->get();
            $purchaseInvoiceItems = PurchaseInvoiceItem::with(
                'product:id,product_description,sku', 
                'purchaseRequest:id,pr_number', 
                'supplier:id,name', 
                'purchasedBy:id,name',
                'invoice:id,invoice_no'
            )
            ->where('cash_ref', $clearInvoice->cash_id)
            ->orderBy('campus') // Sort by campus
            ->orderBy('pi_number') // Then by pi_number
            ->orderBy('created_at') // Finally by created_at
            ->get();

            // Sum paid_amount and group by campus
            $groupedByCampus = PurchaseInvoiceItem::selectRaw('campus, SUM(paid_amount) as total_paid')
                ->where('cash_ref', $clearInvoice->cash_id)
                ->groupBy('campus')
                ->get();
    
            // Conditional rendering based on clear_type
            if ($clearInvoice->clear_type == 1) {
                return Inertia::render('ClearInvoice/ShowPettyCash', [
                    'clearInvoice' => $clearInvoice,
                    'users' => $users,
                    'cashRequests' => $cashRequests,
                    'purchaseInvoiceItems' => $purchaseInvoiceItems,
                    'groupedByCampus' => $groupedByCampus,
                    'approvals' => $approvals,
                    'currentUser' => [
                        'id' => Auth::id(), // Pass the authenticated user's ID
                        'user' => Auth::user(), // Pass the authenticated user
                    ],
                ]);
            } elseif ($clearInvoice->clear_type == 2) {
                return Inertia::render('ClearInvoice/ShowAdvance', [
                    'clearInvoice' => $clearInvoice,
                    'users' => $users,
                    'cashRequests' => $cashRequests,
                    'purchaseInvoiceItems' => $purchaseInvoiceItems,
                    'groupedByCampus' => $groupedByCampus,
                    'approvals' => $approvals,
                    'currentUser' => [
                        'id' => Auth::id(), // Pass the authenticated user's ID
                        'user' => Auth::user(), // Pass the authenticated user
                    ],
                ]);
            }
    
            // Default fallback (optional)
            return redirect()->back()->with('error', 'Invalid clear type.');
        } catch (\Exception $e) {
            \Log::error('Error in show method:', ['message' => $e->getMessage(), 'stack_trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Error fetching clear invoice details.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'clear_type' => 'required',
            'clear_date' => 'required|date',
            'cash_id' => 'required|exists:cash_requests,id|unique:clear_invoices,cash_id', // Ensure cash_id is unique
            'clear_by' => 'required',
            'description' => 'required|string',
            'remark' => 'nullable|string',
        ]);

        // Check if related PurchaseInvoiceItem count is greater than 0
        $purchaseInvoiceItemCount = PurchaseInvoiceItem::where('cash_ref', $request->cash_id)->count();
        if ($purchaseInvoiceItemCount <= 0) {
            return response()->json(['message' => 'Cannot save. No related PurchaseInvoiceItem found.'], 400);
        }

        $clearInvoice = new ClearInvoice($request->all());
        $clearInvoice->ref_no = ClearInvoice::generateRefNo();
        $clearInvoice->status = 0; // Set default status to 0
        $clearInvoice->save();

        // Store approvals
        $this->storeApprovals($clearInvoice->id, $request);

        $clearInvoice->load(['cashRequest:id,ref_no', 'user']); // Reload relationships

        return response()->json($clearInvoice, 201); // Return the newly created clear invoice with cashRequest
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'clear_type' => 'required',
            'clear_date' => 'required|date',
            'cash_id' => 'required|exists:cash_requests,id|unique:clear_invoices,cash_id,' . $id, // Ensure cash_id is unique, excluding the current record
            'clear_by' => 'required',
            'description' => 'required|string',
            'remark' => 'nullable|string',
        ]);

        // Check if related PurchaseInvoiceItem count is greater than 0
        $purchaseInvoiceItemCount = PurchaseInvoiceItem::where('cash_ref', $request->cash_id)->count();
        if ($purchaseInvoiceItemCount <= 0) {
            return response()->json(['message' => 'Cannot update. No related PurchaseInvoiceItem found.'], 400);
        }

        $clearInvoice = ClearInvoice::findOrFail($id);
        $clearInvoice->update($request->all());

        // Update approvals
        $this->storeApprovals($clearInvoice->id, $request);

        $clearInvoice->load(['cashRequest:id,ref_no', 'user']); // Reload relationships

        return response()->json($clearInvoice, 200); // Return the updated clear invoice with cashRequest
    }

    private function storeApprovals($clearInvoiceId, $request)
    {
        // Set docs_type based on clear_type
        if ($request->clear_type == 1) {
            $docsType = 3;
        } elseif ($request->clear_type == 2) {
            $docsType = 4;
        } else {
            return response()->json(['message' => 'Invalid clear_type provided.'], 400);
        }

        $approvalData = [
            ['status_type' => 1, 'user_id' => $request->checked_by], // Checked By
            ['status_type' => 3, 'user_id' => $request->approved_by], // Approved By
        ];

        foreach ($approvalData as $data) {
            if ($data['user_id']) {
                // Check if an approval record already exists 
                $approval = Approval::where([
                    'approval_id' => $clearInvoiceId,
                    'status_type' => $data['status_type'],
                    'docs_type' => $docsType, // Added docs_type criteria here
                ])->first();

                    $approvalName = $this->generateApprovalName($data['status_type']);

                if ($approval) {
                    // Update the existing record
                    $approval->update([
                        'user_id' => $data['user_id'],
                        'docs_type' => $docsType, // Update docs_type
                        'approval_name' => $docsType == 3 ? 'Clear Petty Cash' : 'Clear Advance',
                    ]);
                } else {
                    // Create a new record if it doesn't exist
                    Approval::create([
                        'approval_id' => $clearInvoiceId,
                        'status_type' => $data['status_type'],
                        'docs_type' => $docsType, // Set docs_type
                        'user_id' => $data['user_id'],
                        'approval_name' => $docsType == 3 ? 'Clear Petty Cash' : 'Clear Advance',
                    ]);
                }
            }
        }
    }

    private function generateApprovalName($statusType)
    {
        $statusLabel = match ($statusType) {
            1 => 'Check',
            2 => 'Acknowledge',
            3 => 'Approve',
            4 => 'Receive',
            default => 'Processed',
        };
        return $statusLabel;
    }

    public function destroy($id)
    {
        $clearInvoice = ClearInvoice::findOrFail($id);

        $docsType = match ($clearInvoice->clear_type) {
            1 => 3,
            2 => 4,
            default => null, // Handle unexpected values
        };

        if (!$docsType) {
            return response()->json([
                'message' => 'Invalid request type for determining docs_type.'
            ], 400);
        }

        // Update related PurchaseInvoice records where cash_ref matches clearInvoice cash_id
        PurchaseInvoice::where('cash_ref', $clearInvoice->cash_id)
        ->update(['status' => 0]); // Update PurchaseInvoice status to 0 (Reset)

        CashRequest::where('id', $clearInvoice->cash_id)
        ->update(['status' => 0]);

        // Delete related approval records where docs_type is 3 or 4
        Approval::where([
            'approval_id' => $id,
            'docs_type' => $docsType, // Dynamically set docs_type
        ])->delete();

        // Delete the clear invoice
        $clearInvoice->delete();

        return response()->json(['message' => 'Clear Invoice and related approvals deleted successfully.'], 200);
    }

    public function approve(Request $request, $id)
    {
        try {
            $request->validate([
                'status_type' => 'required|integer',
            ]);

            $currentUser = Auth::user();

            // Determine docs_type based on clear_type
            $clearInvoice = ClearInvoice::findOrFail($id);
            $docsType = match ($cashRequest->clear_type) {
                1 => 3,
                2 => 4,
                default => null, // Handle unexpected values
            };
            if (!$docsType) {
                return response()->json([
                    'message' => 'Invalid request type for determining docs_type.'
                ], 400); // Return a 400 Bad Request response
            }

            // Find or create the approval record for the current user, status type, and docs_type

            $approval = Approval::where([
                'approval_id' => $id,
                'status_type' => $request->status_type,
                'user_id' => $currentUser->id,
                'docs_type' => $docsType, // Added docs_type condition
            ])->first();

            if (!$approval) {
                return response()->json(['message' => 'Approval record not found or unauthorized.'], 403);
            }

            // Update the approval status
            $approval->update([
                'status' => 1, // Update the status to 'approved'
                'click_date' => now(), // Capture the current date
            ]);

            // Update the clear invoice's status based on status_type
            if ($request->status_type == 1) {
                $clearInvoice->status = 1; // Checked
            } elseif ($request->status_type == 3) {
                $clearInvoice->status = 3; // Approved
                // Update related PurchaseInvoice records when statement status is approved
                PurchaseInvoice::where('cash_ref', $clearInvoice->cash_id)
                ->update(['status' => 1]); // Update PurchaseInvoice status to 2 (Approved)
                CashRequest::where('id', $clearInvoice->cash_id)
                ->update(['status' => 1]);
            }
            $clearInvoice->save();

            return response()->json(['message' => 'Approval successful.']);
        } catch (\Exception $e) {
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
            $docsType = match ($cashRequest->clear_type) {
                1 => 3,
                2 => 4,
                default => null, // Handle unexpected values
            };
            if (!$docsType) {
                return response()->json([
                    'message' => 'Invalid request type for determining docs_type.'
                ], 400); // Return a 400 Bad Request response
            }

            // Find or create the approval record for the current user, status type, and docs_type

            $approval = Approval::where([
                'approval_id' => $id,
                'status_type' => $request->status_type,
                'user_id' => $currentUser->id,
                'docs_type' => $docsType, // Added docs_type condition
            ])->first();

            if (!$approval) {
                return response()->json(['message' => 'Approval record not found or unauthorized.'], 403);
            }

            // Update the approval status to rejected
            $approval->update([
                'status' => -1, // Set status to -1 for rejection
                'click_date' => now(), // Capture the current date
            ]);

            $clearInvoice = ClearInvoice::findOrFail($id);
            $clearInvoice->status = -1; // Rejected
            $clearInvoice->save();

            return response()->json(['message' => 'Rejection successful.']);
        } catch (\Exception $e) {
            \Log::error('Rejection Error:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred while processing the rejection.'], 500);
        }
    }

    public function getClearInvoices()
    {
        $clearInvoices = ClearInvoice::with(['cashRequest', 'user'])
            ->select('id', 'ref_no', 'description', 'remark', 'clear_type', 'clear_by', 'status', 'clear_date', 'cash_id')
            ->get();

        return response()->json($clearInvoices); // Return the clear invoices as JSON
    }

    public function getApprovals(ClearInvoice $clearInvoice)
    {
        $approvals = Approval::where('approval_id', $clearInvoice->id)
            ->whereIn('docs_type', [3, 4]) // Filter by docs_type for ClearInvoice
            ->select('status_type', 'user_id')
            ->get();

        return response()->json($approvals);
    }

    public function getPurchaseInvoices(Request $request)
    {
        $request->validate([
            'cash_ref' => 'required|exists:cash_requests,id', // Validate that cash_ref exists in cash_requests
        ]);
    
        try {
            // Fetch purchase invoices with the purchasedBy relationship
            $purchaseInvoices = PurchaseInvoice::where('cash_ref', $request->cash_ref)
                ->where('status', 0)
                ->with('purchasedBy:id,name') // Include the purchasedBy relationship and select only necessary fields
                ->select('id', 'pi_number', 'currency', 'paid_amount', 'purchased_by','status') // Select only the necessary fields
                ->get();
    
            return response()->json($purchaseInvoices, 200); // Return the purchase invoices as JSON
        } catch (\Exception $e) {
            \Log::error('Error fetching purchase invoices:', [
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json(['message' => 'An error occurred while fetching purchase invoices.'], 500);
        }
    }

    public function getPurchaseInvoicesEdit(Request $request)
    {
        $request->validate([
            'cash_ref' => 'required|exists:cash_requests,id', // Validate that cash_ref exists in cash_requests
        ]);
    
        try {
            // Fetch purchase invoices with the purchasedBy relationship
            $purchaseInvoices = PurchaseInvoice::where('cash_ref', $request->cash_ref)
                // ->where('status', 1)
                ->with('purchasedBy:id,name') // Include the purchasedBy relationship and select only necessary fields
                ->select('id', 'pi_number', 'currency', 'paid_amount', 'purchased_by', 'status') // Select only the necessary fields
                ->get();
    
            return response()->json($purchaseInvoices, 200); // Return the purchase invoices as JSON
        } catch (\Exception $e) {
            \Log::error('Error fetching purchase invoices:', [
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json(['message' => 'An error occurred while fetching purchase invoices.'], 500);
        }
    }
}
