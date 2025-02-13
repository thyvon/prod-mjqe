<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use App\Models\PrItem;
use App\Models\PoItems;
use App\Models\CashRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Purchase/Invoices/Index', [
            'purchaseInvoices' => PurchaseInvoice::with(['items', 'supplier'])->get(),
            'users' => User::all(),
            'prItems' => PrItem::with(['product:id,product_description,sku', 'purchaseRequest:id,pr_number,purpose'])->get(),
            'poItems' => PoItems::with(['product:id,product_description,sku', 'purchaseOrder:id,po_number,purpose', 'purchaseRequest:id,pr_number'])->get(),
            'cashRequests' => CashRequest::all(),
            'purchaseRequests' => PurchaseRequest::all(),
            'purchaseOrders' => PurchaseOrder::all(),
            'suppliers' => Supplier::all(),
            'currentUser' => auth()->user(),
        ]);
    }

    public function getPrItems(Request $request)
    {
        $prItems = PrItem::with(['product:id,product_description,sku', 'purchaseRequest:id,pr_number,purpose'])
            ->whereHas('purchaseRequest', fn($query) => $query->where('pr_number', $request->query('pr_number')))
            ->get();

        return response()->json($prItems);
    }

    public function getPoItems(Request $request)
    {
        $poItems = PoItems::with(['product:id,product_description,sku', 'purchaseOrder:id,po_number,purpose', 'purchaseRequest:id,pr_number'])
            ->whereHas('purchaseOrder', fn($query) => $query->where('po_number', $request->query('po_number')))
            ->get();

        return response()->json($poItems);
    }

    public function store(Request $request)
    {
        try {
            Log::info('Store method called', ['request_data' => $request->all()]);

            $rules = [
                'transaction_type' => 'required|integer',
                'cash_ref' => 'nullable|integer|exists:cash_requests,id',
                'payment_type' => 'required|integer',
                'invoice_date' => 'required|date',
                'invoice_no' => 'required|string',
                'supplier' => 'required|integer|exists:suppliers,id',
                'currency' => 'required|integer',
                'currency_rate' => 'required|numeric',
                'payment_term' => 'required|integer',
                'total_amount' => 'required|numeric',
                'paid_amount' => 'required|numeric',
                'created_by' => 'required|integer|exists:users,id',
                'supplier_vat' => 'required|numeric',
                'items' => 'required|array',
                'items.*.pr_item' => 'required|integer|exists:pr_items,id',
                'items.*.po_item' => 'nullable|integer|exists:po_items,id',
                'items.*.description' => 'required|string',
                'items.*.remark' => 'nullable|string',
                'items.*.qty' => 'required|numeric',
                'items.*.uom' => 'required|string',
                'items.*.currency' => 'required|integer',
                'items.*.currency_rate' => 'required|numeric',
                'items.*.unit_price' => 'required|numeric',
                'items.*.discount' => 'nullable|numeric',
                'items.*.vat' => 'nullable|numeric',
                'items.*.return' => 'nullable|numeric',
                'items.*.retention' => 'nullable|numeric',
                'items.*.due_amount' => 'required|numeric',
                'items.*.paid_amount' => 'required|numeric',
                'items.*.requested_by' => 'nullable|integer|exists:users,id',
                'items.*.campus' => 'required|string',
                'items.*.division' => 'required|string',
                'items.*.department' => 'required|string',
                'items.*.location' => 'required|string',
                'items.*.purchased_by' => 'required|integer|exists:users,id',
                'items.*.purpose' => 'required|string',
                'items.*.payment_term' => 'required|integer',
                'items.*.transaction_type' => 'required|integer',
                'items.*.cash_ref' => 'nullable|integer|exists:cash_requests,id',
                'items.*.stop_purchase' => 'nullable|boolean',
                'items.*.asset_type' => 'nullable|integer',
                'items.*.total_usd' => 'required|numeric',
                'items.*.total_khr' => 'required|numeric',
            ];

            if ($request->transaction_type != 2) {
                $rules['cash_ref'] = 'required|integer|exists:cash_requests,id';
            }

            $validatedData = $request->validate($rules);

            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['po_item'])) {
                    $poItem = PoItems::find($itemData['po_item']);
                    $pendingQty = $poItem->qty - $poItem->cancelled_qty - PurchaseInvoiceItem::where('po_item', $poItem->id)->sum('qty');
                    if ($poItem && $itemData['qty'] > $pendingQty) {
                        return response()->json(['error' => 'Validation Error', 'messages' => ['items.*.qty' => ['The quantity of the PO item cannot exceed the pending quantity.']]], 422);
                    }
                }
            }

            if ($validatedData['transaction_type'] == 2) {
                $validatedData['cash_ref'] = null;
            }

            $invoice = PurchaseInvoice::create($validatedData);
            Log::info('Invoice created', ['invoice' => $invoice->toArray()]);

            foreach ($validatedData['items'] as $itemData) {
                $prItem = PrItem::find($itemData['pr_item']);
                if ($prItem) {
                    $itemData['pr_number'] = $prItem->purchase_request_id;
                    $itemData['item_code'] = $prItem->product_id;
                }

                if (isset($itemData['po_item'])) {
                    $poItem = PoItems::find($itemData['po_item']);
                    if ($poItem) {
                        $itemData['po_number'] = $poItem->po_id;
                    }
                }

                $itemData['pi_number'] = $invoice->id;
                $itemData['invoice_date'] = $invoice->invoice_date;
                $itemData['payment_type'] = $invoice->payment_type;
                $itemData['invoice_no'] = $invoice->invoice_no;
                $itemData['supplier'] = $invoice->supplier;
                $itemData['payment_term'] = $invoice->payment_term;
                $itemData['transaction_type'] = $invoice->transaction_type;
                $itemData['requested_by'] = $invoice->created_by;

                $invoiceItem = PurchaseInvoiceItem::create($itemData);
                Log::info('Invoice item created', ['item' => $itemData]);

                if (isset($itemData['po_item'])) {
                    $poItem = PoItems::find($itemData['po_item']);
                    if ($poItem) {
                        $poItem->recalculateReceivedQty();
                    }
                }
            }

            return response()->json($invoice->load('items', 'supplier'), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors(), 'request_data' => $request->all()]);
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error storing invoice', ['exception' => $e, 'request_data' => $request->all(), 'stack_trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function edit($id)
    {
        $invoice = PurchaseInvoice::with(['items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier'])->findOrFail($id);
        return response()->json($invoice);
    }

    public function show($id)
    {
        $invoice = PurchaseInvoice::with(['items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier'])->findOrFail($id);
        return response()->json($invoice);
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Update method called', ['request_data' => $request->all()]);

            $rules = [
                'transaction_type' => 'required|integer',
                'cash_ref' => 'nullable|integer|exists:cash_requests,id',
                'payment_type' => 'required|integer',
                'invoice_date' => 'required|date',
                'invoice_no' => 'required|string',
                'supplier' => 'required|integer|exists:suppliers,id',
                'currency' => 'required|integer',
                'currency_rate' => 'required|numeric',
                'payment_term' => 'required|integer',
                'total_amount' => 'required|numeric',
                'paid_amount' => 'required|numeric',
                'created_by' => 'required|integer|exists:users,id',
                'supplier_vat' => 'required|numeric',
                'items' => 'required|array',
                'items.*.pr_item' => 'required|integer|exists:pr_items,id',
                'items.*.po_item' => 'nullable|integer|exists:po_items,id',
                'items.*.description' => 'required|string',
                'items.*.remark' => 'nullable|string',
                'items.*.qty' => 'required|numeric',
                'items.*.uom' => 'required|string',
                'items.*.currency' => 'required|integer',
                'items.*.currency_rate' => 'required|numeric',
                'items.*.unit_price' => 'required|numeric',
                'items.*.discount' => 'nullable|numeric',
                'items.*.vat' => 'nullable|numeric',
                'items.*.return' => 'nullable|numeric',
                'items.*.retention' => 'nullable|numeric',
                'items.*.due_amount' => 'required|numeric',
                'items.*.paid_amount' => 'required|numeric',
                'items.*.requested_by' => 'nullable|integer|exists:users,id',
                'items.*.campus' => 'required|string',
                'items.*.division' => 'required|string',
                'items.*.department' => 'required|string',
                'items.*.location' => 'required|string',
                'items.*.purchased_by' => 'required|integer|exists:users,id',
                'items.*.purpose' => 'required|string',
                'items.*.payment_term' => 'required|integer',
                'items.*.transaction_type' => 'required|integer',
                'items.*.cash_ref' => 'nullable|integer|exists:cash_requests,id',
                'items.*.stop_purchase' => 'nullable|boolean',
                'items.*.asset_type' => 'nullable|integer',
                'items.*.total_usd' => 'required|numeric',
                'items.*.total_khr' => 'required|numeric',
            ];

            if ($request->transaction_type != 2) {
                $rules['cash_ref'] = 'required|integer|exists:cash_requests,id';
            }

            $validatedData = $request->validate($rules);

            $invoice = PurchaseInvoice::findOrFail($id);
            $existingItems = $invoice->items->keyBy('id');

            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['po_item'])) {
                    $poItem = PoItems::find($itemData['po_item']);
                    
                    // Reset old qty to 0 for the current invoice item before summing
                    PurchaseInvoiceItem::where('po_item', $poItem->id)
                        ->where('id', '!=', $itemData['id'] ?? 0)
                        ->where('pi_number', $invoice->id)
                        ->update(['qty' => 0]);

                    $pendingQty = $poItem->qty - $poItem->cancelled_qty - PurchaseInvoiceItem::where('po_item', $poItem->id)->sum('qty');

                    if ($itemData['qty'] > $pendingQty) {
                        return response()->json(['error' => 'Validation Error', 'messages' => ['items.*.qty' => ['The quantity of the PO item cannot exceed the pending quantity.']]], 422);
                    }
                }
            }

            if ($validatedData['transaction_type'] == 2) {
                $validatedData['cash_ref'] = null;
            }

            $invoice->update($validatedData);

            $existingItemIds = $invoice->items->pluck('id')->toArray();

            foreach ($validatedData['items'] as $itemData) {
                $prItem = PrItem::find($itemData['pr_item']);
                if ($prItem) {
                    $itemData['pr_number'] = $prItem->purchase_request_id;
                    $itemData['item_code'] = $prItem->product_id;
                }

                if (isset($itemData['po_item'])) {
                    $poItem = PoItems::find($itemData['po_item']);
                    if ($poItem) {
                        $itemData['po_number'] = $poItem->po_id;
                    }
                }

                $itemData['invoice_date'] = $invoice->invoice_date;
                $itemData['payment_type'] = $invoice->payment_type;
                $itemData['invoice_no'] = $invoice->invoice_no;
                $itemData['supplier'] = $invoice->supplier;
                $itemData['payment_term'] = $invoice->payment_term;
                $itemData['transaction_type'] = $invoice->transaction_type;
                $itemData['pi_number'] = $invoice->id;
                $itemData['requested_by'] = $invoice->created_by;

                if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
                    $item = PurchaseInvoiceItem::findOrFail($itemData['id']);
                    $item->update($itemData);
                    $existingItemIds = array_diff($existingItemIds, [$itemData['id']]);
                } else {
                    PurchaseInvoiceItem::create($itemData);
                }
            }

            foreach ($existingItemIds as $itemId) {
                $deletedItem = PurchaseInvoiceItem::find($itemId);
                if ($deletedItem) {
                    $deletedItem->delete();
                    if ($deletedItem->po_item) {
                        $poItem = PoItems::find($deletedItem->po_item);
                        if ($poItem) {
                            $poItem->recalculateReceivedQty();
                        }
                    }
                }
            }

            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['po_item'])) {
                    $poItem = PoItems::find($itemData['po_item']);
                    if ($poItem) {
                        $poItem->recalculateReceivedQty();
                    }
                }
            }

            return response()->json($invoice->load('items', 'supplier'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors(), 'request_data' => $request->all()]);
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating invoice', ['exception' => $e, 'request_data' => $request->all(), 'stack_trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function destroy($id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);
        $invoiceItems = $invoice->items;

        foreach ($invoiceItems as $item) {
            if ($item->po_item) {
                $poItem = PoItems::find($item->po_item);
                if ($poItem) {
                    $poItem->recalculateReceivedQty();
                }
            }
        }

        $invoice->items()->delete();
        $invoice->delete();

        foreach ($invoiceItems as $item) {
            if ($item->po_item) {
                $poItem = PoItems::find($item->po_item);
                if ($poItem) {
                    $poItem->recalculateReceivedQty();
                }
            }
        }

        return response()->json(null, 204);
    }

    public function searchSuppliers(Request $request)
    {
        $suppliers = Supplier::where('name', 'like', '%' . $request->get('q', '') . '%')->get();
        return response()->json($suppliers);
    }

    public function getSupplierVat($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json(['vat' => $supplier->vat]);
    }
}
