<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Models\StatementIvoice;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseInvoice; // Import the PurchaseInvoice model
use App\Models\Approval; // Import Approval model
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Facades\Auth; // Import Auth facade

class StatementController extends Controller
{
    public function index()
    {
        $statements = Statement::with([
            'invoices.purchaseInvoice', // Include the purchase_invoice relationship
            'supplier', 
            'clearedBy' // Use 'clearedBy' relationship
        ])
        ->select(
            'id', 
            'statement_number', 
            'supplier_id', 
            'clear_by_id', 
            'clear_date', 
            'total_amount', 
            'total_invoices', 
            'description', 
            'status'
        ) // Ensure 'statement_number' is included
        ->get();

        $suppliers = Supplier::select('id', 'name', 'currency')->get(); // Ensure 'currency' is included

        // Log the retrieved suppliers and their currencies
        \Log::info('Suppliers retrieved with currencies:', $suppliers->toArray());

        $users = User::select('id', 'name')->get();
        $currentUser = auth()->user()->only(['id', 'name']);
        return Inertia::render('ClearInvoice/Statement', [
            'statements' => $statements,
            'suppliers' => $suppliers,
            'users' => $users,
            'currentUser' => (object) $currentUser,
        ]);
    }

    public function getPurchaseInvoices(Request $request)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'clear_date' => 'required|date',
            ]);

            $supplierId = $validated['supplier_id'];
            $clearDate = $validated['clear_date'];

            // Extract month and year from the clear_date
            $month = date('m', strtotime($clearDate));
            $year = date('Y', strtotime($clearDate));

            // Fetch invoices by supplier, month, and year
            $invoices = PurchaseInvoice::where('supplier', $supplierId)
                ->whereMonth('invoice_date', $month)
                ->whereYear('invoice_date', $year)
                ->where('transaction_type', 2) // Add condition for transaction_type = 2
                ->get();

            \Log::info('Fetched Purchase Invoices:', $invoices->toArray()); // Debug log

            return response()->json($invoices);
        } catch (\Exception $e) {
            \Log::error('Error in getPurchaseInvoices:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch purchase invoices.'], 500);
        }
    }

    public function show($id)
        {
            try {
                $statement = Statement::with([
                    'supplier:id,name,currency',
                    'clearedBy:id,name,position,signature',
                    'invoices.purchaseInvoice.items:id,pi_number,paid_amount,campus,department,division', // Include items
                ])->findOrFail($id);
                $approvals = Approval::where('approval_id', $id)
                    ->where('docs_type', 5) 
                    ->with('user:id,name,position,card_id,signature') // Include user details
                    ->get()
                    ->map(function ($approval) {
                        $labels = [
                            1 => 'Checked By',
                            3 => 'Approved By',
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
                    ->values(); // Reindex the collection

                return Inertia::render('ClearInvoice/ShowStatement', [
                    'statement' => $statement,
                    'approvals' => $approvals,
                    'currentUser' => auth()->user()->only(['id', 'name', 'position', 'signature']),
                ]);
            } catch (\Exception $e) {
                \Log::error('Error in show method:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return redirect()->back()->with('error', 'Error fetching statement details.');
            }
        }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'clear_date' => 'required|date',
                'description' => 'nullable|string',
                'remark' => 'nullable|string',
                'status' => 'required|integer',
                'invoices' => 'array', // Validate invoices array
                'invoices.*.id' => 'required|exists:purchase_invoices,id', // Validate each invoice ID
            ]);

            // Check for duplicate invoice IDs in the StatementIvoice table
            $existingInvoices = StatementIvoice::whereIn('invoice_id', collect($validated['invoices'])->pluck('id'))
                ->with('purchaseInvoice:id,pi_number') // Use purchase_invoice relationship
                ->get()
                ->map(function ($statementInvoice) {
                    return $statementInvoice->purchaseInvoice->pi_number ?? 'Unknown'; // Access pi_number via purchase_invoice
                })
                ->toArray();

            if (!empty($existingInvoices)) {
                return response()->json([
                    'error' => 'Some invoices are already included in another statement.',
                    'existing_invoices' => $existingInvoices, // Return pi_number instead of invoice ID
                ], 422);
            }

            $validated['clear_by_id'] = auth()->id(); // Set clear_by_id to the authenticated user's ID
            $validated['statement_number'] = Statement::generateStatementNumber();

            // Create the statement
            $statement = Statement::create($validated);

            $totalAmount = 0;
            $successfulInvoices = 0;

            // Store related invoices and calculate total amount
            foreach ($validated['invoices'] as $invoice) {
                $purchaseInvoice = PurchaseInvoice::findOrFail($invoice['id']);

                $statementInvoice = StatementIvoice::create([
                    'clear_statement_id' => $statement->id,
                    'invoice_id' => $purchaseInvoice->id,
                    'supplier_id' => $validated['supplier_id'],
                    'clear_by_id' => $validated['clear_by_id'],
                    'clear_date' => $validated['clear_date'],
                    'total_amount' => $purchaseInvoice->paid_amount,
                    'status' => 0, // Default status
                ]);

                if ($statementInvoice) {
                    $totalAmount += $purchaseInvoice->paid_amount;
                    $successfulInvoices++;
                }
            }

            // Update the statement's total_amount and total_invoices only if invoices were successfully stored
            if ($successfulInvoices > 0) {
                $statement->update([
                    'total_amount' => $totalAmount,
                    'total_invoices' => $successfulInvoices,
                ]);
            }

            $this->storeApprovals($statement->id, $request);
            // Load the supplier relationship
            $statement->load(['supplier:id,name,currency', 'invoices', 'clearedBy:id,name']); // Use 'clearedBy' relationship

            return response()->json($statement, 201); // Return the statement with the supplier relationship
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Error in store method:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'clear_by_id' => 'sometimes|exists:users,id',
                'supplier_id' => 'sometimes|exists:suppliers,id',
                'clear_date' => 'sometimes|date',
                'description' => 'nullable|string',
                'remark' => 'nullable|string',
                'status' => 'sometimes|integer',
                'invoices' => 'array', // Validate invoices array
                'invoices.*.id' => 'required|exists:purchase_invoices,id', // Validate each invoice ID
            ]);

            $statement = Statement::findOrFail($id);

            // Extract existing invoice IDs from the database
            $existingInvoiceIds = StatementIvoice::where('clear_statement_id', $id)->pluck('invoice_id')->toArray();

            // Extract new invoice IDs from the request
            $newInvoiceIds = collect($validated['invoices'])->pluck('id')->toArray();

            // Identify invoices to be removed
            $invoicesToRemove = array_diff($existingInvoiceIds, $newInvoiceIds);

            // Remove invoices that are no longer in the request
            StatementIvoice::where('clear_statement_id', $id)
                ->whereIn('invoice_id', $invoicesToRemove)
                ->delete();

            // Filter out invoices that are already in the statement
            $newInvoices = collect($validated['invoices'])->filter(function ($invoice) use ($existingInvoiceIds) {
                return !in_array($invoice['id'], $existingInvoiceIds);
            });

            // Validate only new invoices for duplicates
            $duplicateInvoices = StatementIvoice::whereIn('invoice_id', $newInvoices->pluck('id'))
                ->with('purchaseInvoice:id,pi_number') // Use purchase_invoice relationship
                ->get()
                ->map(function ($statementInvoice) {
                    return $statementInvoice->purchaseInvoice->pi_number ?? 'Unknown'; // Access pi_number via purchase_invoice
                })
                ->toArray();

            if (!empty($duplicateInvoices)) {
                return response()->json([
                    'error' => 'Some invoices are already included in another statement.',
                    'existing_invoices' => $duplicateInvoices, // Return pi_number instead of invoice ID
                ], 422);
            }

            $statement->update($validated);

            $totalAmount = 0;
            $successfulInvoices = 0;

            // Add new invoices and calculate total amount
            foreach ($newInvoices as $invoice) {
                $purchaseInvoice = PurchaseInvoice::findOrFail($invoice['id']);

                $statementInvoice = StatementIvoice::create([
                    'clear_statement_id' => $statement->id,
                    'invoice_id' => $purchaseInvoice->id,
                    'supplier_id' => $validated['supplier_id'] ?? $statement->supplier_id,
                    'clear_by_id' => $validated['clear_by_id'] ?? $statement->clear_by_id,
                    'clear_date' => $validated['clear_date'] ?? $statement->clear_date,
                    'total_amount' => $purchaseInvoice->paid_amount,
                    'status' => 0, // Default status
                ]);

                if ($statementInvoice) {
                    $totalAmount += $purchaseInvoice->paid_amount;
                    $successfulInvoices++;
                }
            }

            // Update the statement's total_amount and total_invoices only if invoices were successfully stored
            if ($successfulInvoices > 0 || count($invoicesToRemove) > 0) {
                $statement->update([
                    'total_amount' => $statement->total_amount + $totalAmount - StatementIvoice::whereIn('invoice_id', $invoicesToRemove)->sum('paid_amount'),
                    'total_invoices' => $statement->total_invoices + $successfulInvoices - count($invoicesToRemove),
                ]);
            }

            $this->storeApprovals($statement->id, $request);
            // Load the supplier relationship
            $statement->load(['supplier:id,name,currency', 'invoices', 'clearedBy:id,name']); // Ensure currency is included

            return response()->json($statement, 200); // Return the statement with the supplier relationship
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Error in update method:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    private function storeApprovals($statementId, $request)
    {
        // Set docs_type based on clear_type
        $docsType = 5;

        $approvalData = [
            ['status_type' => 1, 'user_id' => $request->checked_by], // Checked By
            ['status_type' => 3, 'user_id' => $request->approved_by], // Approved By
        ];

        foreach ($approvalData as $data) {
            if ($data['user_id']) {
                // Check if an approval record already exists
                $approval = Approval::where('approval_id', $statementId)
                    ->where('status_type', $data['status_type']) // Correctly chain the where conditions
                    ->where('docs_type', $docsType) // Add docs_type condition
                    ->first();

                    $approvalName = $this->generateApprovalName($data['status_type']);

                if ($approval) {
                    // Update the existing record
                    $approval->update([
                        'user_id' => $data['user_id'],
                        'docs_type' => $docsType, // Update docs_type
                        'approval_name' => 'Clear Statement-'.$approvalName, // Set approval_name based on docs_type
                    ]);
                } else {
                    // Create a new record if it doesn't exist
                    Approval::create([
                        'approval_id' => $statementId,
                        'status_type' => $data['status_type'],
                        'docs_type' => $docsType, // Set docs_type
                        'user_id' => $data['user_id'],
                        'approval_name' => 'Clear Statement-'.$approvalName, // Set approval_name based on docs_type
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
        try {
            // Start a database transaction
            \DB::beginTransaction();

            $statement = Statement::with('invoices')->findOrFail($id);
            // Check if the statement has related invoices
            if ($statement->invoices->isNotEmpty()) {
                $invoiceIds = $statement->invoices->pluck('invoice_id')->toArray();

                // Perform a bulk update for all related PurchaseInvoice records
                $updated = PurchaseInvoice::whereIn('id', $invoiceIds)->update(['status' => 0]);

                \Log::info('Updated PurchaseInvoice statuses to 0', [
                    'invoice_ids' => $invoiceIds,
                    'updated_count' => $updated,
                ]);
            }
            // Delete related StatementIvoice records
            StatementIvoice::where('clear_statement_id', $id)->delete();

            Approval::where([
                'approval_id' => $statement->id,
                'docs_type' => 5, // Dynamically set docs_type
            ])->delete();

            // Delete the statement
            $statement->delete();

            // Commit the transaction
            \DB::commit();

            return response()->json(['message' => 'Statement and related invoices deleted successfully']);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            \DB::rollBack();

            \Log::error('Error deleting statement:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to delete statement.'], 500);
        }
    }

    public function searchSuppliers(Request $request)
    {
        $query = $request->input('q');
        $suppliers = Supplier::search($query); // Call the search function from the Supplier model
        return response()->json($suppliers);
    }

    public function edit($id)
    {
        try {
            $statement = Statement::with([
                'invoices.purchaseInvoice:id,pi_number,invoice_no,invoice_date,total_amount,paid_amount', // Include pi_number and other fields
                'supplier:id,name,currency', // Include supplier details
                'clearedBy:id,name' // Include cleared by user details
            ])->findOrFail($id);

            return response()->json($statement, 200); // Return the statement data
        } catch (\Exception $e) {
            \Log::error('Error in edit method:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch statement data.'], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $request->validate([
                'status_type' => 'required|integer',
            ]);

            $currentUser = Auth::user();

            // Determine docs_type based on clear_type
            $statement = Statement::findOrFail($id);
            $docsType = 5;

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
                $statement->status = 1; // Checked
            } elseif ($request->status_type == 3) {
                $statement->status = 3; // Approved
            // Update related PurchaseInvoice records when statement status is approved
                foreach ($statement->invoices as $statementInvoice) { // Using the invoices relationship
                    $purchaseInvoice = PurchaseInvoice::find($statementInvoice->invoice_id);
                    if ($purchaseInvoice) {
                        $purchaseInvoice->update(['status' => 1]); // Update PurchaseInvoice status to 2 (Approved)
                    }
                }
            }
            $statement->save();

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
            $docsType = 5;

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

            $statement = Statement::findOrFail($id);
            $statement->status = -1; // Rejected
            $statement->save();

            return response()->json(['message' => 'Rejection successful.']);
        } catch (\Exception $e) {
            \Log::error('Rejection Error:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred while processing the rejection.'], 500);
        }
    }

    public function getApprovals(Statement $statement)
    {
        // Fetch approvals for the specific statement
        $approvals = Approval::where('approval_id', $statement->id)
            ->whereIn('docs_type', [5]) // Filter by docs_type for statements
            ->select('status_type', 'user_id')
            ->get();

        return response()->json($approvals);
    }
}
