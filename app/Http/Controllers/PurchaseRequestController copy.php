<?php

// app/Http/Controllers/PurchaseRequestController.php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\PurchaseRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        // Eager load the requestBy relationship
        $purchaseRequests = PurchaseRequest::with('requestBy')->get();
        return Inertia::render('Purchase/Pr/Index', ['purchaseRequests' => $purchaseRequests]);
    }

    public function create()
    {
        $currentUser = auth()->user(); // Get the current logged-in user

        return Inertia::render('Purchase/Pr/Create', [
            'currentUser' => $currentUser, // Pass the current user as a prop to the Vue component
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pr_number' => 'required|string|unique:purchase_requests',
            'request_date' => 'required|date',
            'request_by' => 'required|exists:users,id',
            'campus' => 'required|string',
            'division' => 'required|string',
            'department' => 'required|string',
            'purpose' => 'nullable|string',
            'is_urgent' => 'boolean',
        ]);

        // Create the purchase request
        PurchaseRequest::create($validated);

        // Redirect to the index page with a flash message
        return redirect()->route('purchase-requests.index')->with('success', 'Purchase request submitted successfully!');
    }


    public function edit($id)
    {
        $currentUser = auth()->user();
        $purchaseRequest = PurchaseRequest::findOrFail($id);
        return Inertia::render('Purchase/Pr/Edit', [
            'currentUser' => $currentUser,
            'purchaseRequest' => $purchaseRequest]);
    }

    public function update(Request $request, $id)
    {
    // Validate the incoming data
    $validated = $request->validate([
        'pr_number' => [
            'nullable', // Allow it to be empty
            'string',
            Rule::unique('purchase_requests', 'pr_number')->ignore($id), // Ignore current record for uniqueness
        ],
        'request_date' => 'required|date',
        'request_by' => 'required|exists:users,id',
        'campus' => 'required|string',
        'division' => 'required|string',
        'department' => 'required|string',
        'purpose' => 'required|string',
        'is_urgent' => 'nullable|boolean',
    ]);

            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->update($validated);
            return redirect()->route('purchase-requests.index')->with('success', 'Purchase request updated successfully!');
    }



    public function destroy($id)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($id);
        $purchaseRequest->delete();
        return redirect()->route('purchase-requests.index');
    }
}
