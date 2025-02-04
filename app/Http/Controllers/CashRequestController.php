<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CashRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CashRequestController extends Controller
{
    // Display all cash requests with their user details
    public function index()
    {
        $cashRequests = CashRequest::with('user:id,name')->get(); // Only load necessary fields
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
                'request_date' => 'required|date_format:Y-m-d',
                'user_id' => 'required|integer|exists:users,id',
                'position' => 'required|string|max:255',
                'id_card' => 'required|string|max:255',
                'campus' => 'required|string|max:255',
                'division' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'currency' => 'required|string|max:10',
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

        $validated['ref_no'] = CashRequest::generateRefNo($validated['request_type']);
        $validated['request_by'] = User::findOrFail($validated['user_id'])->name;

        $cashRequest = CashRequest::create($validated);

        // Load the relationships
        $cashRequest->load('user:id,name');

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
                'currency' => 'required|string|max:10',
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

        // Update the request_by field
        $validated['request_by'] = User::findOrFail($validated['user_id'])->name;

        // Update the cash request
        $cashRequest->update($validated);

        // Load the relationships
        $cashRequest->load('user:id,name');

        // Return success response
        return response()->json($cashRequest);
    }

    // Delete the specified cash request
    public function destroy(CashRequest $cashRequest)
    {
        $cashRequest->delete();

        // Return success response
        return response()->json(['message' => 'Cash request deleted successfully!']);
    }

    // Display the specified cash request
    public function show(CashRequest $cashRequest)
    {
        $cashRequest->load('user:id,name');
        return Inertia::render('CashRequest/Show', [
            'cashRequest' => $cashRequest,
            'users' => User::all(), // Ensure all users are passed to the view
        ]);
    }
}
