<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    // Fetch all suppliers
    public function index()
    {
        return Inertia::render('Suppliers/Index', [
            'suppliers' => Supplier::all()
        ]);
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kh_name' => 'required|string|max:255',
            'number' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'address' => 'required|string',
            'payment_term' => 'required|string',
            'currency' => 'required|integer', // Ensure currency is an integer
            'vat' => 'nullable|numeric', // Add vat field
            'status' => 'required|in:0,1', // Ensures status is either 0 or 1
        ]);

        $supplier = Supplier::create($validated);
        return response()->json($supplier, 201); // Return the newly created supplier
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kh_name' => 'required|string|max:255',
            'number' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'address' => 'required|string',
            'payment_term' => 'required|string',
            'currency' => 'required|integer', // Ensure currency is an integer
            'vat' => 'nullable|numeric', // Add vat field
            'status' => 'required|in:0,1', // Ensures status is either 0 or 1
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($validated);
        return response()->json($supplier, 200); // Return the updated supplier
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(null, 204);
    }

    public function getVat($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            return response()->json(['vat' => $supplier->vat], 200); // Return VAT of the supplier
        } catch (\Exception $e) {
            Log::error('Error fetching supplier VAT:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch VAT.'], 500);
        }
    }
}
