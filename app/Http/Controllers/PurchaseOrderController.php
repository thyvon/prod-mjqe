<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\{PoItems, Product, User, Supplier, PurchaseOrder, PurchaseInvoiceItem, PrItem, PurchaseRequest};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    // Display a list of purchase orders
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['poItems.prItem.product', 'user', 'supplier'])->get();
        return Inertia::render('Purchase/Po/Index', [
            'purchaseOrders' => $purchaseOrders->map(fn($po) => [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'date' => $po->date,
                'supplier' => ['name' => $po->supplier->name],
                'total_amount_usd' => $po->total_amount_usd,
                'purpose' => $po->purpose,
                'vat' => $po->vat,
                'status' => $po->status,
                'items' => $po->poItems->map(fn($item) => [
                    'id' => $item->id,
                    'pr_item_id' => $item->pr_item_id,
                    'description' => $item->description,
                    'concatenated_description' => $item->prItem->product->product_description . ' | ' . $item->description,
                    'pr_description' => $item->prItem->product->product_description . ' | ' . $item->prItem->remark,
                    'qty' => $item->qty,
                    'uom' => $item->uom,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_usd,
                    'pr_number' => $item->prItem->purchaseRequest->pr_number,
                    'sku' => $item->prItem->product->sku,
                    'campus' => $item->campus,
                    'division' => $item->division,
                    'department' => $item->department,
                    'location' => $item->location,
                    'discount' => $item->discount,
                    'vat' => $item->vat,
                ]),
            ]),
            'currentUser' => auth()->user(),
            'suppliers' => Supplier::all(),
            'prItems' => PrItem::with('product', 'purchaseRequest')->get(),
            'purchaseRequests' => PurchaseRequest::all(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    // Store a newly created purchase order with items
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // --- Begin PR item qty validation ---
        foreach ($request->items as $item) {
            $prItem = PrItem::find($item['pr_item_id']);
            if (!$prItem) {
                return response()->json(['error' => 'PR item not found.'], 400);
            }
            // Calculate available qty for PO
            $availableQty = $prItem->qty - $prItem->qty_cancel - $prItem->qty_purchase - $prItem->qty_po;
            if ($item['qty'] > $availableQty) {
                return response()->json([
                    'error' => "PO item qty ({$item['qty']}) cannot exceed available PR item qty ({$availableQty}).",
                    'pr_item_id' => $prItem->id
                ], 400);
            }
        }

        try {
            $po_number = $this->generatePoNumber();
            $items = $request->items;
            $totals = $this->calculateTotals($items, $request->currency_rate);

            $purchaseOrder = PurchaseOrder::create(array_merge($request->all(), [
                'po_number' => $po_number,
                'total_item' => count($items),
                'total_amount_usd' => $totals['usd'],
                'total_amount_khr' => $totals['khr'],
                'due_amount' => $totals['usd'],
                'discount' => $request->discount ?? 0,
                'vat' => $request->vat ?? 0,
                'purchased_by' => auth()->id(),
            ]));

            $this->createOrUpdatePoItems($purchaseOrder, $items, $request);

            foreach ($items as $item) {
                $prItem = PrItem::find($item['pr_item_id']);
                if ($prItem) {
                    $prItem->calculateQtyPurchaseOrder();
                }
            }

            $purchaseOrder->load(['poItems.prItem.product', 'user', 'supplier']);
            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            \Log::error('Error creating purchase order: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['error' => 'Failed to create purchase order'], 500);
        }
    }

    // Display a specific purchase order
    public function show($id)
    {
        return Inertia::render('Purchase/Po/Show', [
            'purchaseOrder' => PurchaseOrder::with('poItems.prItem.purchaseRequest', 'poItems.prItem.product','supplier','purchaser')->findOrFail($id),
        ]);
    }

    public function getInvoiceItems($poNumber)
    {
        try {
            $invoiceItems = PurchaseInvoiceItem::with('product', 'invoice', 'supplier', 'purchasedBy')
                ->where('po_number', $poNumber)->get();
            return response()->json($invoiceItems);
        } catch (\Exception $e) {
            \Log::error('Error fetching invoice items:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error fetching invoice items.'], 500);
        }
    }

    // Show the form to edit an existing purchase order
    public function edit($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::with(['poItems.prItem.product', 'poItems.prItem.purchaseRequest', 'user', 'supplier'])->findOrFail($id);
            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            \Log::error('Error in edit method:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error fetching purchase order.'], 500);
        }
    }

    // Update an existing purchase order with items
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), $this->rules($id, true));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // --- Begin PR item qty validation ---
        foreach ($request->items as $item) {
            $prItem = PrItem::find($item['pr_item_id']);
            if (!$prItem) {
                return response()->json(['error' => 'PR item not found.'], 400);
            }
            // Calculate available qty for PO
            $availableQty = $prItem->qty - $prItem->qty_cancel - $prItem->qty_purchase - $prItem->qty_po;
            if ($item['qty'] > $availableQty) {
                return response()->json([
                    'error' => "PO item qty ({$item['qty']}) cannot exceed available PR item qty ({$availableQty}).",
                    'pr_item_id' => $prItem->id
                ], 400);
            }
        }

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            $items = $request->items;
            $totals = $this->calculateTotals($items, $request->currency_rate);

            $purchaseOrder->update(array_merge($request->all(), [
                'total_item' => count($items),
                'total_amount_usd' => $totals['usd'],
                'total_amount_khr' => $totals['khr'],
                'due_amount' => $totals['usd'],
                'discount' => $request->discount ?? 0,
                'vat' => $request->vat ?? 0,
                'purchased_by' => auth()->id(),
            ]));

            $purchaseOrder->poItems()->delete();
            $this->createOrUpdatePoItems($purchaseOrder, $items, $request);

            foreach ($items as $item) {
                $prItem = PrItem::find($item['pr_item_id']);
                if ($prItem) {
                    $prItem->calculateQtyPurchaseOrder();
                }
            }

            $purchaseOrder->load(['poItems.prItem.product', 'user', 'supplier']);
            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            \Log::error('Error updating purchase order: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['error' => 'Failed to update purchase order'], 500);
        }
    }

    // Delete a purchase order and its items
    public function destroy($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Call calculateQtyPurchaseOrder for each affected PR item before deletion
            foreach ($purchaseOrder->poItems as $poItem) {
                $prItem = PrItem::find($poItem->pr_item_id);
                if ($prItem) {
                    $prItem->calculateQtyPurchaseOrder();
                }
            }
            $purchaseOrder->poItems()->delete();
            $purchaseOrder->delete();
            return response()->json(['success' => 'Purchase order deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting purchase order.'], 500);
        }
    }

    public function searchSuppliers(Request $request)
    {
        $query = $request->input('q');
        return response()->json(Supplier::where('name', 'like', "%$query%")->get());
    }

    // --- Helper Methods ---

    private function rules($id = null, $isUpdate = false)
    {
        $uniquePo = $isUpdate ? 'sometimes|required|string|unique:purchase_orders,po_number,' . $id : '';
        return [
            'date' => $isUpdate ? 'sometimes|required|date' : 'required|date',
            'currency' => 'required|integer',
            'currency_rate' => $isUpdate ? 'sometimes|required|numeric' : 'required|numeric',
            'payment_term' => $isUpdate ? 'sometimes|required|string' : 'required|string',
            'purpose' => $isUpdate ? 'sometimes|required|string' : 'required|string',
            'po_number' => $uniquePo,
            'supplier_id' => $isUpdate ? 'sometimes|required|exists:suppliers,id' : 'required|exists:suppliers,id',
            'items' => $isUpdate ? 'sometimes|required|array' : 'required|array',
            'items.*.pr_item_id' => 'required|exists:pr_items,id',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.campus' => 'required|string',
            'items.*.division' => 'required|string',
            'items.*.department' => 'required|string',
            'items.*.location' => 'required|string',
            'items.*.purchaser_id' => 'required|exists:users,id',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.vat' => 'nullable|numeric|min:0',
            'items.*.total_khr' => $isUpdate ? 'sometimes|required|numeric' : 'required|numeric',
        ];
    }

    private function generatePoNumber()
    {
        $date = now();
        $count = PurchaseOrder::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->count() + 1;
        return 'PO-' . $date->format('Y-m') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function calculateTotals($items, $currency_rate)
    {
        $usd = 0;
        $khr = 0;
        foreach ($items as $item) {
            $itemTotal = (($item['qty'] * $item['unit_price']) - ($item['discount'] ?? 0)) * (1 + ($item['vat'] ?? 0) / 100);
            $usd += $itemTotal;
            $khr += $itemTotal * $currency_rate;
        }
        return ['usd' => $usd, 'khr' => $khr];
    }

    private function createOrUpdatePoItems($purchaseOrder, $items, $request)
    {
        foreach ($items as $item) {
            $prItem = PrItem::with('purchaseRequest')->findOrFail($item['pr_item_id']);
            $total_usd = (($item['qty'] * $item['unit_price']) - ($item['discount'] ?? 0)) * (1 + ($item['vat'] ?? 0) / 100);
            $purchaseOrder->poItems()->create([
                'po_id' => $purchaseOrder->id,
                'pr_item_id' => $prItem->id,
                'pr_id' => $prItem->purchase_request_id,
                'product_id' => $prItem->product_id,
                'supplier_id' => $request->supplier_id,
                'campus' => $item['campus'],
                'division' => $item['division'],
                'department' => $item['department'],
                'location' => $item['location'],
                'description' => $item['description'],
                'qty' => $item['qty'],
                'uom' => $prItem->uom,
                'currency' => $request->currency,
                'currency_rate' => $request->currency_rate,
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'vat' => $item['vat'] ?? 0,
                'grand_total' => $total_usd,
                'total_usd' => $total_usd,
                'total_khr' => $total_usd * $request->currency_rate,
                'received_qty' => $item['received_qty'] ?? 0,
                'cancelled_qty' => $item['cancelled_qty'] ?? 0,
                'pending' => $item['pending'] ?? 0,
                'purchaser_id' => $item['purchaser_id'],
                'is_cancelled' => $item['is_cancelled'] ?? false,
                'cancelled_reason' => $item['cancelled_reason'] ?? null,
                'status' => $item['status'] ?? 'Pending',
            ]);
        }
    }
}