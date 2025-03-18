<?php

namespace App\Http\Controllers;

use App\Models\ClearInvoice;
use App\Models\User;
use App\Models\CashRequest;
use App\Models\PurchaseInvoiceItem;
use Illuminate\Http\Request;
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
            $clearInvoice = ClearInvoice::with(['cashRequest.user:id,name', 'user:id,name'])->findOrFail($id); // Ensure user and cashRequest relationships are loaded
            $users = User::select('id', 'name')->get();
            $cashRequests = CashRequest::with('user:id,name')->select('id', 'ref_no', 'request_type', 'status', 'user_id','request_date','amount')->get(); // Include user relationship
            $purchaseInvoiceItems = PurchaseInvoiceItem::with('product:id,product_description,sku')->where('cash_ref', $clearInvoice->cash_id)->get(); // Query PurchaseInvoiceItem with product

            return Inertia::render('ClearInvoice/Show', [
                'clearInvoice' => $clearInvoice,
                'users' => $users,
                'cashRequests' => $cashRequests,
                'purchaseInvoiceItems' => $purchaseInvoiceItems, // Pass the queried data to the Vue component
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
        $clearInvoice->load(['cashRequest:id,ref_no', 'user']); // Reload relationships

        return response()->json($clearInvoice, 200); // Return the updated clear invoice with cashRequest
    }

    public function destroy($id)
    {
        $clearInvoice = ClearInvoice::findOrFail($id);
        $clearInvoice->delete();

        return response()->json(['message' => 'Clear Invoice deleted successfully.'], 200); // Return a JSON response
    }

    public function approve($id)
    {
        $clearInvoice = ClearInvoice::findOrFail($id);
        $clearInvoice->status = 1; // Set status to 'Approved'
        $clearInvoice->save();

        // Call approve method from CashRequest
        $cashRequest = CashRequest::findOrFail($clearInvoice->cash_id);
        $cashRequest->approve();

        return response()->json($clearInvoice->load('cashRequest:id,ref_no'), 200); // Return the updated clear invoice with cashRequest
    }

    public function getClearInvoices()
    {
        $clearInvoices = ClearInvoice::with(['cashRequest', 'user'])
            ->select('id', 'ref_no', 'description', 'clear_type', 'clear_by', 'status', 'clear_date', 'cash_id')
            ->get();

        return response()->json($clearInvoices); // Return the clear invoices as JSON
    }
}
