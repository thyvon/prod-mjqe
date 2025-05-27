<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CashRequest;
use App\Models\Approval; // Import Approval model
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CashRequestController extends Controller
{
    // Display all cash requests with their user details
    public function index()
    {
        $cashRequests = CashRequest::with('user:id,name,position,card_id,campus,division,department')->get(); // Ensure all necessary fields are loaded
        $users = User::all();
        $currentUser = Auth::user();

        return Inertia::render('CashRequest/Index', [
            'cashRequests' => $cashRequests,
            'users' => $users,
            'currentUser' => $currentUser,
        ]);
    }

    // Store a new cash request
    public function store(Request $request)
    {
        // Debugging: Log the request data
        \Log::info('Store Cash Request Data:', $request->all());

        // Validation...
        try {
            $validated = $request->validate([
                'request_type' => 'required|integer', // Changed to integer
                'request_date' => 'required|date',
                'user_id' => 'required|integer|exists:users,id',
                'position' => 'required|string|max:255',
                'id_card' => 'required|string|max:255',
                'campus' => 'required|string|max:255',
                'division' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'currency' => 'required|integer', // Ensure currency is validated as an integer
                'exchange_rate' => 'required|numeric|min:0',
                'amount' => 'required|numeric|min:0',
                'via' => 'required|string|max:255',
                'reason' => 'nullable|string|max:1000',
                'remark' => 'nullable|string|max:1000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Errors:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Debugging: Log the validated data
        \Log::info('Validated Cash Request Data:', $validated);

        // Ensure the currency is saved correctly
        $validated['currency'] = (int) $validated['currency'];

        $validated['ref_no'] = CashRequest::generateRefNo($validated['request_type']);
        $validated['request_by'] = User::findOrFail($validated['user_id'])->name;

        // Calculate amount_usd
        $validated['amount_usd'] = $validated['currency'] == 1 
            ? $validated['amount'] 
            : $validated['amount'] / $validated['exchange_rate'];

        \Log::info('Final Validated Data:', $validated); // Debugging log

        $cashRequest = CashRequest::create($validated);

        // Store approvals
        $this->storeApprovals($cashRequest->id, $request);

        // Load the user relationship
        $cashRequest->load('user:id,name,position,card_id,campus,division,department');

        // Include approval_status in the response
        $cashRequest->approval_status = 0; // Default status

        // Return success response
        return response()->json($cashRequest, 201);
    }

    // Update the specified cash request
    public function update(Request $request, CashRequest $cashRequest)
    {
        // Validation...
        try {
            $validated = $request->validate([
                'request_type' => 'required|integer', // Changed to integer
                'request_date' => 'required|date',
                'user_id' => 'required|integer|exists:users,id',
                'position' => 'required|string|max:255',
                'id_card' => 'required|string|max:255',
                'campus' => 'required|string|max:255',
                'division' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'currency' => 'required|integer', // Ensure currency is validated as an integer
                'exchange_rate' => 'required|numeric|min:0',
                'amount' => 'required|numeric|min:0',
                'via' => 'required|string|max:255',
                'reason' => 'nullable|string|max:1000',
                'remark' => 'nullable|string|max:1000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Errors:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Ensure the currency is saved correctly
        $validated['currency'] = (int) $validated['currency'];

        // Update the request_by field
        $validated['request_by'] = User::findOrFail($validated['user_id'])->name;

        // Calculate amount_usd
        $validated['amount_usd'] = $validated['currency'] == 1 
            ? $validated['amount'] 
            : $validated['amount'] / $validated['exchange_rate'];

        // Update the cash request
        $cashRequest->update($validated);

        // Update approvals
        $this->storeApprovals($cashRequest->id, $request);

        // Load the user relationship
        $cashRequest->load('user:id,name,position,card_id,campus,division,department');

        // Include approval_status in the response
        $cashRequest->approval_status = $cashRequest->approval_status ?? 0;

        // Return success response
        return response()->json($cashRequest);
    }

    // Helper function to store approvals
    private function storeApprovals($cashRequestId, $request)
    {
        $docsType = $request->request_type; // Set docs_type based on request_type
    
        $approvalData = [
            ['status_type' => 1, 'user_id' => $request->checked_by],
            ['status_type' => 2, 'user_id' => $request->acknowledged_by],
            ['status_type' => 3, 'user_id' => $request->approved_by],
            ['status_type' => 4, 'user_id' => $request->received_by],
        ];

        foreach ($approvalData as $data) {
            if ($data['user_id']) {
                // Check if an approval record already exists with docs_type criteria
                $approval = Approval::where([
                    'approval_id' => $cashRequestId,
                    'status_type' => $data['status_type'],
                    'docs_type' => $docsType, // Added docs_type criteria here
                ])->first();

                $approvalName = $this->generateApprovalName($data['status_type']);
    
                if ($approval) {
                    // Update the existing record
                    $approval->update([
                        'user_id' => $data['user_id'],
                        'docs_type' => $docsType, // Update docs_type
                        'approval_name' => $docsType == 1 ? 'Cash Request-'.$approvalName : 'Cash Advance-'.$approvalName, // Set approval_name based on docsType
                    ]);
                } else {
                    // Create a new record if it doesn't exist
                    Approval::create([
                        'approval_id' => $cashRequestId,
                        'status_type' => $data['status_type'],
                        'docs_type' => $docsType, // Set docs_type
                        'user_id' => $data['user_id'],
                        'approval_name' => $docsType == 1 ? 'Cash Request-'.$approvalName : 'Cash Advance-'.$approvalName, // Set approval_name based on docsType
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

    // Fetch approvals for a specific cash request
    public function getApprovals(CashRequest $cashRequest)
    {
        $approvals = Approval::where('approval_id', $cashRequest->id)
            ->whereIn('docs_type', [1, 2]) // Filter by docs_type
            ->select('status_type', 'user_id')
            ->get();

        return response()->json($approvals);
    }

    // Delete the specified cash request
    public function destroy(CashRequest $cashRequest)
    {
        // Check if the cash request is associated with the purchase_invoice table
        $isAssociated = \DB::table('purchase_invoices')
            ->where('cash_ref', $cashRequest->id) // Ensure the column name matches your schema
            ->exists();

        if ($isAssociated) {
            return response()->json([
                'message' => 'Cannot delete this cash request because it is associated with a purchase invoice.'
            ], 400); // Return a 400 Bad Request response
        }

        // Map docs_type dynamically (modify logic as per your requirements)
        $docsType = match ($cashRequest->request_type) { // Assuming request_type is a property of CashRequest
            1 => 1, // Map type 1 to docs_type 6
            2 => 2, // Map type 2 to docs_type 7
            default => null, // Handle unexpected values
        };

        if (!$docsType) {
            return response()->json([
                'message' => 'Invalid request type for determining docs_type.'
            ], 400);
        }

        Approval::where([
            'approval_id' => $cashRequest->id,
            'docs_type' => $docsType, // Dynamically set docs_type
        ])->delete();

        // Delete the cash request
        $cashRequest->delete();

        // Return success response
        return response()->json(['message' => 'Cash request and related approvals deleted successfully!']);
    }

    // Display the specified cash request
    public function show(CashRequest $cashRequest)
    {
        $cashRequest->load('user:id,name,position,card_id,campus,division,department,signature'); // Include 'signature' field

        // Map currency to its string representation
        $cashRequest->currency = $cashRequest->currency == 1 ? 'USD' : ($cashRequest->currency == 2 ? 'KHR' : 'Unknown');

        // Fetch approvals for the cash request
        $approvals = Approval::where('approval_id', $cashRequest->id)
            ->whereIn('docs_type', [1, 2]) // Filter by docs_type (1 or 2)
            ->with('user:id,name,position,card_id,campus,division,department,signature') // Include 'signature' field
            ->orderBy('status_type', 'asc') // Add this line to sort by status_type
            ->get()
            ->map(function ($approval) {
                $labels = [
                    1 => 'Checked By',
                    2 => 'Acknowledged By',
                    3 => 'Approved By',
                    4 => 'Received By',
                ];

                return [
                    'label' => $labels[$approval->status_type] ?? 'Unknown',
                    'user_id' => $approval->user_id,
                    'name' => $approval->user->name ?? '',
                    'position' => $approval->user->position ?? '',
                    'card_id' => $approval->user->card_id ?? '',
                    'campus' => $approval->user->campus ?? '',
                    'division' => $approval->user->division ?? '',
                    'department' => $approval->user->department ?? '',
                    'date' => $approval->updated_at->format('Y-m-d'),
                    'signature' => $approval->user->signature ?? null,
                    'status_type' => $approval->status_type,
                    'status' => $approval->status,
                    'click_date' => $approval->click_date,
                ];
            })
            ->values(); // Reindex the collection

        // Pass the authenticated user in the `auth` object
        return Inertia::render('CashRequest/Show', [
            'cashRequest' => $cashRequest,
            'approvals' => $approvals,
            'currentUser' => [
                'user' => Auth::user(), // Pass the authenticated user
            ],
        ]);
    }

    // Handle approval action
    public function approve(Request $request, CashRequest $cashRequest)
    {
        try {
            $request->validate([
                'status_type' => 'required|integer',
            ]);

            $currentUser = Auth::user();
            $docsType = match ($cashRequest->request_type) { // Assuming `request_type` is a property of CashRequest
                1 => 1, // Map request_type 1 to docs_type 6
                2 => 2, // Map request_type 2 to docs_type 7
                default => null, // Handle unexpected values
            };

            if (!$docsType) {
                return response()->json([
                    'message' => 'Invalid request type for determining docs_type.'
                ], 400); // Return a 400 Bad Request response
            }
    
            // Find the approval record for the current user, status type, and docs_type
            $approval = Approval::where([
                'approval_id' => $cashRequest->id,
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

            // Update the cash request's approval_status based on status_type
            $cashRequest->update([
                'approval_status' => $request->status_type,
            ]);

            return response()->json(['message' => 'Approval successful.']);
        } catch (\Exception $e) {
            \Log::error('Approval Error:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred while processing the approval.'], 500);
        }
    }

    // Handle rejection action
    public function reject(Request $request, CashRequest $cashRequest)
    {
        try {
            $request->validate([
                'status_type' => 'required|integer',
            ]);

            $currentUser = Auth::user();
            $docsType = match ($cashRequest->request_type) { // Assuming `request_type` is a property of CashRequest
                1 => 1, // Map request_type 1 to docs_type 6
                2 => 2, // Map request_type 2 to docs_type 7
                default => null, // Handle unexpected values
            };

            if (!$docsType) {
                return response()->json([
                    'message' => 'Invalid request type for determining docs_type.'
                ], 400); // Return a 400 Bad Request response
            }

            // Find the approval record for the current user and status type
            $approval = Approval::where('approval_id', $cashRequest->id)
                ->where('status_type', $request->status_type)
                ->where('user_id', $currentUser->id)
                ->where('docs_type', $docsType) // Added docs_type condition
                ->first();
    
            if (!$approval) {
                return response()->json(['message' => 'Approval record not found or unauthorized.'], 403);
            }

            // Update the approval status to rejected
            $approval->update([
                'status' => -1, // Set status to -1 for rejection
                'click_date' => now(), // Capture the current date
            ]);

            $cashRequest->update([
                'approval_status' => -1, // Update the cash request's approval status to rejected
            ]);

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
