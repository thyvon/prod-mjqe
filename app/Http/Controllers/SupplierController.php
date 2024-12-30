<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    // Fetch all suppliers
    public function index()
    {
        // $suppliers = Supplier::all();
        // return Inertia::render('Suppliers/Index', ['suppliers' => $suppliers]);
        return Inertia::render('Suppliers/Index', [
            'suppliers' => Supplier::all()
          ]);
    }


    // Show the form for creating a new supplier
    // public function create()
    // {
    //     return Inertia::render('Supplier/Create');
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kh_name' => 'required|string|max:255',
            'number' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'address' => 'required|string',
            'payment_term' => 'required|string',
            'status' => 'required|in:0,1', // Ensures status is either 0 or 1
        ]);

        Supplier::create($validated);
        // return redirect()->route('suppliers.index');
    }

    // Show the form for editing a supplier
    // public function edit(Supplier $supplier)
    // {
    //     return Inertia::render('Supplier/Edit', [
    //         'supplier' => $supplier
    //     ]);
    // }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kh_name' => 'required|string|max:255',
            'number' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'address' => 'required|string',
            'payment_term' => 'required|string',
            'status' => 'required|in:0,1', // Ensures status is either 0 or 1
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($validated);
        // return redirect()->route('suppliers.index');
    }

    // Delete the specified supplier
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        // return redirect()->route('suppliers.index');
    }
}
