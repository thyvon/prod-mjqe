<?php

namespace App\Http\Controllers;

use App\Models\ClearInvoice;
use App\Models\User;
use App\Models\CashRequest;
use App\Models\PurchaseInvoiceItem;
use App\Models\Approval; // Import Approval model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClearInvoiceController extends Controller
{
    public function index()
    {
        $clearInvoices = ClearInvoice::with(['cashRequest:id,ref_no', 'user:id,name']) // Adjust relationships as needed
            ->select('id', 'ref_no', 'description', 'clear_type', 'clear_by', 'status', 'clear_date', 'cash_id') // Ensure 'cash_id' is selected
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
            $clearInvoice = ClearInvoice::with(['cashRequest.user:id,name', 'user:id,name,card_id,position,phone'])->findOrFail($id);

            // Fetch approvals for the clear invoice
            $approvals = Approval::where('approval_id', $id)
                ->whereIn('docs_type', [3, 4]) // Filter by docs_type for ClearInvoice
                ->with('user:id,name,position,card_id,signature') // Include 'signature' field
                ->get()
                ->map(function ($approval) {
                    $labels = [
                        1 => 'Checked By',
                        2 => 'Approved By',
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
            $purchaseInvoiceItems = PurchaseInvoiceItem::with('product:id,product_description,sku')->where('cash_ref', $clearInvoice->cash_id)->get();

            // Sum paid_amount and group by campus
            $groupedByCampus = PurchaseInvoiceItem::selectRaw('campus, SUM(paid_amount) as total_paid')
                ->where('cash_ref', $clearInvoice->cash_id)
                ->groupBy('campus')
                ->get();

            return Inertia::render('ClearInvoice/Show', [
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
            'description' => 'nullable|string',
            'status' => 'required',
        ]);

        $clearInvoice = new ClearInvoice($request->all());
        $clearInvoice->ref_no = ClearInvoice::generateRefNo();
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
            'description' => 'nullable|string',
            'status' => 'required',
        ]);

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
        $docsType = $request->clear_type == 1 ? 3 : 4;

        $approvalData = [
            ['status_type' => 1, 'user_id' => $request->checked_by], // Checked By
            ['status_type' => 2, 'user_id' => $request->approved_by], // Approved By
        ];

        foreach ($approvalData as $data) {
            if ($data['user_id']) {
                // Check if an approval record already exists
                $approval = Approval::where('approval_id', $clearInvoiceId)
                    ->where('status_type', $data['status_type']) // Correctly chain the where conditions
                    ->where('docs_type', $docsType) // Add docs_type condition
                    ->first();

                if ($approval) {
                    // Update the existing record
                    $approval->update([
                        'user_id' => $data['user_id'],
                        'docs_type' => $docsType, // Update docs_type
                        'approval_name' => $docsType == 3 ? 'Clear Petty Cash' : 'Clear Advance', // Set approval_name based on docs_type
                    ]);
                } else {
                    // Create a new record if it doesn't exist
                    Approval::create([
                        'approval_id' => $clearInvoiceId,
                        'status_type' => $data['status_type'],
                        'docs_type' => $docsType, // Set docs_type
                        'user_id' => $data['user_id'],
                        'approval_name' => $docsType == 3 ? 'Clear Petty Cash' : 'Clear Advance', // Set approval_name based on docs_type
                    ]);
                }
            }
        }
    }

    public function destroy($id)
    {
        $clearInvoice = ClearInvoice::findOrFail($id);

        // Delete related approval records where docs_type is 3 or 4
        Approval::where('approval_id', $id)
            ->whereIn('docs_type', [3, 4])
            ->delete();

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
            $docsType = $clearInvoice->clear_type == 1 ? 3 : 4;

            // Find or create the approval record for the current user, status type, and docs_type
            $approval = Approval::firstOrCreate(
                [
                    'approval_id' => $id,
                    'status_type' => $request->status_type,
                    'user_id' => $currentUser->id,
                    'docs_type' => $docsType,
                ],
                [
                    'approval_name' => $docsType == 3 ? 'Clear Petty Cash' : 'Clear Advance',
                    'status' => 0, // Default status
                ]
            );

            // Update the approval status
            $approval->update([
                'status' => 1, // Update the status to 'approved'
                'click_date' => now(), // Capture the current date
            ]);

            // Update the clear invoice's status based on status_type
            if ($request->status_type == 1) {
                $clearInvoice->status = 1; // Checked
            } elseif ($request->status_type == 2) {
                $clearInvoice->status = 2; // Approved
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
            ->select('id', 'ref_no', 'description', 'clear_type', 'clear_by', 'status', 'clear_date', 'cash_id')
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
}
