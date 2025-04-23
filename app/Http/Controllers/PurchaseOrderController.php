<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\PoItems;
use App\Models\Product;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoiceItem;
use App\Models\PrItem;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    // Display a list of purchase orders
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['poItems.prItem.product', 'user', 'supplier'])->get();
        $currentUser = auth()->user();
        $suppliers = Supplier::all();
        $prItems = PrItem::with('product', 'purchaseRequest')->get();
        $purchaseRequests = PurchaseRequest::all();

        // Log the purchase orders and their items
        \Log::info('Purchase Orders:', $purchaseOrders->toArray());

        return Inertia::render('Purchase/Po/Index', [
            'purchaseOrders' => $purchaseOrders->map(function ($purchaseOrder) {
                \Log::info('Purchase Order:', $purchaseOrder->toArray());
                return [
                    'id' => $purchaseOrder->id,
                    'po_number' => $purchaseOrder->po_number,
                    'date' => $purchaseOrder->date,
                    'supplier' => ['name' => $purchaseOrder->supplier->name],
                    'total_amount_usd' => $purchaseOrder->total_amount_usd,
                    'purpose' => $purchaseOrder->purpose,
                    'vat' => $purchaseOrder->vat,
                    'status' => $purchaseOrder->status,
                    'items' => $purchaseOrder->poItems->map(function ($item) {
                        \Log::info('PO Item:', $item->toArray());
                        return [
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
                        ];
                    }),
                ];
            }),
            'currentUser' => $currentUser,
            'suppliers' => $suppliers,
            'prItems' => $prItems,
            'purchaseRequests' => $purchaseRequests,
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    // Store a newly created purchase order with items
    public function store(Request $request)
    {
        \Log::info('Store method called with request data: ' . json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'currency' => 'required|integer',
            'currency_rate' => 'required|numeric',
            'payment_term' => 'required|string',
            'purpose' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'purchased_by' => 'nullable|exists:users,id',
            'supplier_id' => 'required|exists:suppliers,id',
            // 'status' => 'required|string|in:Pending,Approved,Rejected,Cancelled',
            'items' => 'required|array',
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
            'items.*.total_khr' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Generate unique po_number
            $date = now();
            $count = PurchaseOrder::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count() + 1;
            $po_number = 'PO-' . $date->format('Y-m') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            \Log::info('Creating Purchase Order with PO Number: ' . $po_number);

            $purchaseOrderData = array_merge($request->all(), [
                'po_number' => $po_number,
                'total_item' => count($request->items),
                'total_amount_usd' => array_sum(array_map(function($item) use ($request) {
                    return (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                }, $request->items)),
                'total_amount_khr' => array_sum(array_map('floatval', array_column($request->items, 'total_khr'))),
                'due_amount' => array_sum(array_map(function($item) use ($request) {
                    return (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                }, $request->items)),
                'discount' => $request->discount ?? 0,
                'vat' => $request->vat ?? 0,
                'purchased_by' => auth()->id(), // Automatically capture the authenticated user's ID
            ]);

            \Log::info('Purchase Order Data:', $purchaseOrderData);

            $purchaseOrder = PurchaseOrder::create($purchaseOrderData);
            \Log::info('Purchase Order created with ID: ' . $purchaseOrder->id);

            foreach ($request->items as $item) {
                $prItem = PrItem::with('purchaseRequest')->findOrFail($item['pr_item_id']);
                $total_usd = (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                $grand_total = (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                $poItemData = [
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
                    'vat' => $item['vat'],
                    'grand_total' => $grand_total,
                    'total_usd' => $total_usd,
                    'total_khr' => $total_usd * $request->currency_rate,
                    'received_qty' => $item['received_qty'] ?? 0,
                    'cancelled_qty' => $item['cancelled_qty'] ?? 0,
                    'pending' => $item['pending'] ?? 0,
                    'purchaser_id' => $item['purchaser_id'],
                    'is_cancelled' => $item['is_cancelled'] ?? false,
                    'cancelled_reason' => $item['cancelled_reason'] ?? null,
                    'status' => $item['status'] ?? 'Pending',
                ];

                \Log::info('PO Item Data:', $poItemData);

                $purchaseOrder->poItems()->create($poItemData);
            }

            $purchaseOrder->load(['poItems.prItem.product', 'user', 'supplier']);

            \Log::info('Purchase Order created successfully: ' . json_encode($purchaseOrder->toArray()));
            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            \Log::error('Error creating purchase order: ' . $e->getMessage());
            \Log::error('Request data: ' . json_encode($request->all()));
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
                ->where('po_number', $poNumber)
                ->get();
            \Log::info('Invoice items fetched successfully:', ['invoice_items' => $invoiceItems->toArray()]);
            return response()->json($invoiceItems);
        } catch (\Exception $e) {
            \Log::error('Error fetching invoice items:', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error fetching invoice items.'], 500);
        }
    }

    // private function getPurchaseOrderWithRelations($id)
    // {
    //     $purchaseOrder = PurchaseOrder::with([
    //         'poItems.prItem.product',
    //         'poItems.prItem.purchaseRequest',
    //         'user',
    //         'supplier'
    //     ])->findOrFail($id);

    //     $purchaseOrder->poItems->each(function ($item) {
    //         $item->remaining_qty = $item->qty - $item->cancelled_qty - $item->received_qty;
    //     });

    //     return $purchaseOrder;
    // }

    // Show the form to edit an existing purchase order
    public function edit($id)
    {
        try {
            \Log::info('Edit method called with ID:', ['id' => $id]);
            $purchaseOrder = PurchaseOrder::with(['poItems.prItem.product', 'poItems.prItem.purchaseRequest', 'user', 'supplier'])->findOrFail($id);
            \Log::info('Editing Purchase Order:', ['purchaseOrder' => $purchaseOrder->toArray()]);
            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            \Log::error('Error in edit method:', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error fetching purchase order.'], 500);
        }
    }

    // Update an existing purchase order with items
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'currency' => 'required|integer',
            'currency_rate' => 'sometimes|required|numeric',
            'payment_term' => 'sometimes|required|string',
            'purpose' => 'sometimes|required|string',
            'po_number' => 'sometimes|required|string|unique:purchase_orders,po_number,' . $id,
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            // 'status' => 'sometimes|required|string|in:Pending,Approved,Rejected,Cancelled',
            'items' => 'sometimes|required|array',
            'items.*.pr_item_id' => 'required|exists:pr_items,id',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.campus' => 'required|string',
            'items.*.division' => 'required|string',
            'items.*.department' => 'required|string',
            'items.*.location' => 'required|string',
            'items.*.purchaser_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            $purchaseOrderData = array_merge($request->all(), [
                'total_item' => count($request->items),
                'total_amount_usd' => array_sum(array_map(function($item) use ($request) {
                    return (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                }, $request->items)),
                'total_amount_khr' => array_sum(array_map('floatval', array_column($request->items, 'total_khr'))),
                'due_amount' => array_sum(array_map(function($item) use ($request) {
                    return (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                }, $request->items)),
                'discount' => $request->discount ?? 0,
                'vat' => $request->vat ?? 0,
                'purchased_by' => auth()->id(), // Automatically capture the authenticated user's ID	
            ]);

            \Log::info('Purchase Order Data:', $purchaseOrderData);

            $purchaseOrder->update($purchaseOrderData);

            $purchaseOrder->poItems()->delete();
            foreach ($request->items as $item) {
                $prItem = PrItem::with('purchaseRequest')->findOrFail($item['pr_item_id']);
                $total_usd = (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                $grand_total = (($item['qty'] * $item['unit_price']) - $item['discount']) * (1 + ($item['vat'] / 100));
                $poItemData = [
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
                    'vat' => $item['vat'],
                    'grand_total' => $grand_total,
                    'total_usd' => $total_usd,
                    'total_khr' => $total_usd * $request->currency_rate,
                    'received_qty' => $item['received_qty'] ?? 0,
                    'cancelled_qty' => $item['cancelled_qty'] ?? 0,
                    'pending' => $item['pending'] ?? 0,
                    'purchaser_id' => $item['purchaser_id'],
                    'is_cancelled' => $item['is_cancelled'] ?? false,
                    'cancelled_reason' => $item['cancelled_reason'] ?? null,
                    'status' => $item['status'] ?? 'Pending',
                ];

                \Log::info('PO Item Data:', $poItemData);

                $purchaseOrder->poItems()->create($poItemData);
            }

            $purchaseOrder->load(['poItems.prItem.product', 'user', 'supplier']);

            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            \Log::error('Error updating purchase order: ' . $e->getMessage());
            \Log::error('Request data: ' . json_encode($request->all()));
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Failed to update purchase order'], 500);
        }
    }

    // Cancel a purchase order
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancelled_reason' => 'required|string|max:255',
        ]);

        try {
            $purchaseOrder = PurchaseOrder::with('poItems.prItem.product', 'user', 'supplier')->findOrFail($id);

            // Check if any item has received_qty > 0
            foreach ($purchaseOrder->poItems as $item) {
                if ($item->received_qty > 0) {
                    return response()->json(['error' => 'Cannot cancel purchase order with received items.'], 400);
                }
            }

            $purchaseOrder->cancel($request->cancelled_reason);

            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            \Log::error('Error canceling purchase order: ' . $e->getMessage());
            return response()->json(['error' => 'Error canceling purchase order.'], 500);
        }
    }

    // Cancel individual PO items
    public function cancelItem(Request $request, $id)
    {
        $request->validate([
            'cancelled_reason' => 'required|string|max:255',
            'cancelled_qty' => 'required|numeric|min:0',
        ]);

        try {
            $poItem = PoItems::with('prItem.product', 'purchaseOrder')->findOrFail($id);
            $remaining_qty = $poItem->qty - $poItem->cancelled_qty - $poItem->received_qty;

            if ($request->cancelled_qty > $remaining_qty) {
                return response()->json(['error' => 'Cancelled quantity cannot exceed remaining quantity.'], 400);
            }

            $poItem->cancelItem($request->cancelled_reason, $request->cancelled_qty);

            return response()->json($poItem); // Return the cancelled item instead of the whole purchase order
        } catch (\Exception $e) {
            \Log::error('Error canceling PO item: ' . $e->getMessage());
            return response()->json(['error' => 'Error canceling PO item.'], 500);
        }
    }

    // Delete a purchase order and its items
    public function destroy($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
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
        $suppliers = Supplier::where('name', 'like', '%' . $query . '%')->get();
        return response()->json($suppliers);
    }
}
