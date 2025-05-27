<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\PrItem;
use App\Models\Product;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return Inertia::render('Purchase/Pr/Index', [
            'purchaseRequests' => PurchaseRequest::with(['prItems.product:id,product_description,sku,uom', 'requestBy:id,name'])->get(),
            'currentUser' => auth()->user(),
            'products' => Product::select('id', 'product_description', 'sku', 'uom')->get(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_date' => 'required|date',
                'request_by' => 'required|exists:users,id',
                'campus' => 'required|string',
                'division' => 'required|string',
                'department' => 'required|string',
                'purpose' => 'nullable|string',
                'is_urgent' => 'boolean',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.remark' => 'nullable|string',
                'items.*.qty' => 'required|numeric|min:1',
                'items.*.uom' => 'required|string',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.total_price' => 'required|numeric|min:0',
                'items.*.campus' => 'required|string',
                'items.*.division' => 'required|string',
                'items.*.department' => 'required|string',
            ]);

            $validated['total_amount'] = collect($validated['items'])->sum('total_price');
            $validated['status'] = 'Pending';
            $validated['is_urgent'] = $request->input('is_urgent') ? 1 : 0;

            $purchaseRequest = PurchaseRequest::create($validated);

            foreach ($validated['items'] as $item) {
                $item['qty_last'] = $item['qty'];
                $purchaseRequest->prItems()->create($item);
            }

            return response()->json($purchaseRequest->load(['prItems.product', 'requestBy']), 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating purchase request: ' . $e->getMessage());
            return response()->json(['error' => 'Error creating purchase request.'], 500);
        }
    }

    public function edit($id)
    {
        try {
            $purchaseRequest = PurchaseRequest::with(['prItems.product:id,product_description,sku,uom', 'requestBy:id,name'])->findOrFail($id);
            return response()->json($purchaseRequest);
        } catch (\Exception $e) {
            \Log::error('Error in edit method:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error fetching purchase request.'], 500);
        }
    }

    public function show($id)
    {
        return Inertia::render('Purchase/Pr/Show', [
            'purchaseRequest' => PurchaseRequest::with(['prItems.product:id,product_description,sku,uom'])->findOrFail($id),
            'products' => Product::select('id', 'product_description', 'sku', 'uom')->get(),
            'users' => User::select('id', 'name', 'position','card_id','phone', 'extension')->get(),
        ]);
    }

    public function getInvoiceItems($prNumber)
    {
        try {
            $invoiceItems = PurchaseInvoiceItem::with('product:id,product_description,sku,uom', 'invoice:id,pi_number', 'supplier:id,name', 'purchasedBy:id,name')
                ->where('pr_number', $prNumber)
                ->get();
            return response()->json($invoiceItems);
        } catch (\Exception $e) {
            \Log::error('Error fetching invoice items:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error fetching invoice items.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'pr_number' => [
                    'required',
                    'string',
                    Rule::unique('purchase_requests', 'pr_number')->ignore($id),
                ],
                'request_date' => 'required|date',
                'request_by' => 'required|exists:users,id',
                'campus' => 'required|string',
                'division' => 'required|string',
                'department' => 'required|string',
                'purpose' => 'nullable|string',
                'is_urgent' => 'boolean',
                'items' => 'required|array',
                'items.*.id' => 'nullable|exists:pr_items,id',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.remark' => 'nullable|string',
                'items.*.qty' => 'required|numeric|min:1',
                'items.*.uom' => 'required|string',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.total_price' => 'required|numeric|min:0',
                'items.*.campus' => 'required|string',
                'items.*.division' => 'required|string',
                'items.*.department' => 'required|string',
            ]);

            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->update($validated);
            
            $existingItemIds = collect($validated['items'])->pluck('id')->filter();
            $purchaseRequest->prItems()->whereNotIn('id', $existingItemIds)->delete();
            foreach ($validated['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    PrItem::find($itemData['id'])->update($itemData);
                } else {
                    $purchaseRequest->prItems()->create($itemData);
                }
            }

            $purchaseRequest->update([
                'total_amount' => $purchaseRequest->prItems->sum('total_price'),
                'is_urgent' => $request->input('is_urgent') ? 1 : 0
            ]);

            return response()->json($purchaseRequest->load(['prItems.product', 'requestBy']), 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating purchase request: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating purchase request.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $purchaseRequest->prItems()->delete();
            $purchaseRequest->delete();

            return response()->json(['success' => 'Purchase request deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting purchase request.'], 500);
        }
    }
}
