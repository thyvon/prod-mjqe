<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Models\StatementIvoice;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseInvoice; // Import the PurchaseInvoice model
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log; // Import Log facade

class StatementController extends Controller
{
    public function index()
    {
        $statements = Statement::with([
            'invoices.purchaseInvoice', // Include the purchase_invoice relationship
            'supplier', 
            'clearedBy'
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
        $statement = Statement::with(['supplier', 'clearedBy']) // Exclude 'invoices' relationship
            ->findOrFail($id);

        return Inertia::render('ClearInvoice/StatementShow', [
            'statement' => $statement,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'clear_date' => 'required|date',
                'description' => 'nullable|string',
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

            // Load the supplier relationship
            $statement->load(['supplier:id,name,currency', 'invoices', 'clearedBy:id,name']); // Ensure currency is included

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
                    'total_amount' => $statement->total_amount + $totalAmount - StatementIvoice::whereIn('invoice_id', $invoicesToRemove)->sum('total_amount'),
                    'total_invoices' => $statement->total_invoices + $successfulInvoices - count($invoicesToRemove),
                ]);
            }

            // Load the supplier relationship
            $statement->load(['supplier:id,name,currency', 'invoices', 'clearedBy:id,name']); // Ensure currency is included

            return response()->json($statement, 200); // Return the statement with the supplier relationship
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Error in update method:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id)
    {
        $statement = Statement::findOrFail($id);

        // Delete related invoices
        StatementIvoice::where('clear_statement_id', $id)->delete();

        $statement->delete();

        return response()->json(['message' => 'Statement and related invoices deleted successfully']);
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
}
