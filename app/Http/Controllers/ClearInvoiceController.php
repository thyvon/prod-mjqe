<?php

namespace App\Http\Controllers;

use App\Models\ClearInvoice;
use App\Models\User;
use App\Models\CashRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClearInvoiceController extends Controller
{
    public function index()
    {
        $clearInvoices = ClearInvoice::with(['cashRequest', 'user']) // Adjust relationships as needed
            ->select('id', 'ref_no', 'description', 'clear_type', 'clear_by', 'status', 'clear_date', 'cash_id') // Ensure 'cash_id' is selected
            ->get();

        $cashRequests = CashRequest::where('status', 0)->select('id', 'ref_no', 'request_type','ref_no')->get(); // Include 'request_type' in the selection
        $users = User::select('id', 'name')->get();
        $currentUser = auth()->user(); // Get the current user

        return Inertia::render('ClearInvoice/Index', [
            'clearInvoices' => $clearInvoices,
            'cashRequests' => $cashRequests,
            'users' => $users,
            'currentUser' => $currentUser, // Pass the current user to the Vue component
        ]);
    }

    public function show($id)
    {
        $clearInvoice = ClearInvoice::with('cashRequest:id,ref_no')->findOrFail($id);
        $users = User::all();
        $cashRequests = CashRequest::all();

        return Inertia::render('ClearInvoice/Show', [
            'clearInvoice' => $clearInvoice,
            'users' => $users,
            'cashRequests' => $cashRequests,
        ]);
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

        return response()->json($clearInvoice, 201); // Return the newly created clear invoice
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

        return response()->json($clearInvoice, 200); // Return the updated clear invoice
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

        return response()->json($clearInvoice, 200); // Return the updated clear invoice
    }
}
