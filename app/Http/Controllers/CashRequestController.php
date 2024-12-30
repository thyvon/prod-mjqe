<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CashRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CashRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cashRequests = CashRequest::all();

        return Inertia::render('CashRequest/Index', [
            'cashRequests' => $cashRequests,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $requestTypes = ['Petty Cash', 'Cash Advance'];
        $users = User::all();
        $currentUser = Auth::user();
        $defaultRequestType = $requestTypes[0]; // Default to 'Petty Cash'
        $refNo = CashRequest::generateRefNo($defaultRequestType);

        return Inertia::render('CashRequest/Create', [
            'users' => $users,
            'currentUser' => $currentUser,
            'requestTypes' => $requestTypes,
            'refNo' => $refNo,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $validated['ref_no'] = CashRequest::generateRefNo($validated['request_type']);
        $validated['request_by'] = User::findOrFail($validated['user_id'])->name;

        CashRequest::create($validated);

        return redirect()
            ->route('cash-request.index')
            ->with('success', 'Cash request created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cashRequest = CashRequest::findOrFail($id);
        $users = User::all(); // Fetch all users or use a specific query to get the user details

        return Inertia::render('CashRequest/Show', [
            'cashRequest' => $cashRequest,
            'users' => $users,
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashRequest $cashRequest)
    {
        $users = User::all();

        return Inertia::render('CashRequest/Edit', [
            'cashRequest' => $cashRequest,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashRequest $cashRequest)
    {
        $validated = $this->validateRequest($request);

        $cashRequest->update($validated);

        return redirect()
            ->route('cash-request.index')
            ->with('success', 'Cash request updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashRequest $cashRequest)
    {
        $cashRequest->delete();

        return redirect()
            ->route('cash-request.index')
            ->with('success', 'Cash request deleted successfully!');
    }

    /**
     * Validate incoming request data for create or update.
     */
    private function validateRequest(Request $request)
    {
        return $request->validate([
            'request_type' => 'required|string|max:255',
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
    }
}
