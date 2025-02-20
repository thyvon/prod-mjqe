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
        return response()->json(
            PrItem::with(['product:id,product_description,sku', 'purchaseRequest:id,pr_number,purpose'])
                ->whereHas('purchaseRequest', fn($query) => $query->where('pr_number', $request->query('pr_number')))
                ->get()
        );
    }

    public function getPoItems(Request $request)
    {
        return response()->json(
            PoItems::with(['product:id,product_description,sku', 'purchaseOrder:id,po_number,purpose', 'purchaseRequest:id,pr_number'])
                ->whereHas('purchaseOrder', fn($query) => $query->where('po_number', $request->query('po_number')))
                ->get()
        );
    }

    public function store(Request $request)
    {
        try {
            Log::info('Store method called', ['request_data' => $request->all()]);

            $rules = $this->getValidationRules($request->transaction_type);
            $validatedData = $request->validate($rules);

            $this->validateItemQuantities($validatedData['items']);

            if ($validatedData['transaction_type'] == 2) {
                $validatedData['cash_ref'] = null;
            }

            $invoice = PurchaseInvoice::create($validatedData);
            Log::info('Invoice created', ['invoice' => $invoice->toArray()]);

            $this->createOrUpdateInvoiceItems($invoice, $validatedData['items']);

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
        try {
            $invoice = PurchaseInvoice::with(['items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier'])->findOrFail($id);
            return response()->json([
                'invoice' => $invoice,
                'vat_rate' => $invoice->vat_rate, // Ensure VAT rate is retrieved
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in edit method:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error fetching invoice.'], 500);
        }
    }

    public function show($id)
    {
        return response()->json(
            PurchaseInvoice::with(['items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier'])->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Update method called', ['request_data' => $request->all()]);

            $rules = $this->getValidationRules($request->transaction_type);
            $validatedData = $request->validate($rules);

            $invoice = PurchaseInvoice::findOrFail($id);
            $existingItems = $invoice->items->keyBy('id');

            $this->validateItemQuantities($validatedData['items'], $invoice->id);

            if ($validatedData['transaction_type'] == 2) {
                $validatedData['cash_ref'] = null;
            }

            $invoice->update($validatedData);

            $updatedItemIds = $this->createOrUpdateInvoiceItems($invoice, $validatedData['items'], $existingItems);

            $this->deleteRemovedItems($existingItems, $updatedItemIds);

            $this->recalculateItemQuantities($validatedData['items']);

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

    private function getValidationRules($transactionType)
    {
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
            'sub_total' => 'nullable|numeric',
            'vat_rate' => 'nullable|numeric', // Ensure VAT is saved to the invoice table
            'vat_amount' => 'nullable|numeric',
            'discount_total' => 'nullable|numeric',
            'service_charge' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'paid_amount' => 'required|numeric',
            'created_by' => 'required|integer|exists:users,id',
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

        if ($transactionType != 2) {
            $rules['cash_ref'] = 'required|integer|exists:cash_requests,id';
        }

        return $rules;
    }

    private function validateItemQuantities($items, $invoiceId = null)
    {
        $itemQuantities = [];
        foreach ($items as $itemData) {
            if (isset($itemData['po_item'])) {
                if (!isset($itemQuantities['po_item'][$itemData['po_item']])) {
                    $itemQuantities['po_item'][$itemData['po_item']] = 0;
                }
                $itemQuantities['po_item'][$itemData['po_item']] += $itemData['qty'];
            }

            if (isset($itemData['pr_item'])) {
                if (!isset($itemQuantities['pr_item'][$itemData['pr_item']])) {
                    $itemQuantities['pr_item'][$itemData['pr_item']] = 0;
                }
                $itemQuantities['pr_item'][$itemData['pr_item']] += $itemData['qty'];
            }
        }

        foreach ($items as $itemData) {
            if (isset($itemData['po_item'])) {
                $poItem = PoItems::find($itemData['po_item']);

                $poQty = $poItem->qty - $poItem->cancelled_qty;
                $receivedQty = PurchaseInvoiceItem::where('po_item', $poItem->id)
                    ->when($invoiceId, fn($query) => $query->where('pi_number', '!=', $invoiceId))
                    ->sum('qty');

                $pendingQty = $poQty - $receivedQty;

                if ($itemQuantities['po_item'][$itemData['po_item']] > $pendingQty) {
                    $product = $poItem->product;
                    $sku = $product ? $product->sku : 'unknown';
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items.*.qty' => ['The qty of Item: ' . $sku . ' cannot exceed the pending qty in PO.']
                    ]);
                }
            }

            if (isset($itemData['pr_item'])) {
                $prItem = PrItem::find($itemData['pr_item']);

                $prQty = $prItem->qty - $prItem->qty_cancel;
                $receivedQty = PurchaseInvoiceItem::where('pr_item', $prItem->id)
                    ->when($invoiceId, fn($query) => $query->where('pi_number', '!=', $invoiceId))
                    ->sum('qty');

                $pendingQty = $prQty - $receivedQty;

                if ($itemQuantities['pr_item'][$itemData['pr_item']] > $pendingQty) {
                    $product = $prItem->product;
                    $sku = $product ? $product->sku : 'unknown';
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items.*.qty' => ['The qty of Item: ' . $sku . ' cannot exceed the pending qty in PR.']
                    ]);
                }
            }
        }
    }

    private function createOrUpdateInvoiceItems($invoice, $items, $existingItems = null)
    {
        $updatedItemIds = [];
        $itemCount = count($items);
        $serviceChargePerItem = $itemCount > 0 ? $invoice->service_charge / $itemCount : 0;

        foreach ($items as $itemData) {
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

            // Calculate total price for the item
            $itemData['total_price'] = $itemData['qty'] * $itemData['unit_price'];

            // Distribute service charge evenly across all items
            $itemData['service_charge'] = floatval($serviceChargePerItem);

            $itemData['discount_total'] = $invoice->discount_total;

            if ($existingItems && isset($itemData['id']) && $existingItems->has($itemData['id'])) {
                $existingItem = $existingItems->get($itemData['id']);
                $existingItem->update($itemData);
                $updatedItemIds[] = $existingItem->id;
            } else {
                $newItem = PurchaseInvoiceItem::create($itemData);
                $updatedItemIds[] = $newItem->id;
            }
        }

        return $updatedItemIds;
    }

    private function deleteRemovedItems($existingItems, $updatedItemIds)
    {
        $itemsToRecalculate = [];

        foreach ($existingItems as $itemId => $item) {
            if (!in_array($itemId, $updatedItemIds)) {
                $poItemId = $item->po_item;
                $prItemId = $item->pr_item;
                $item->delete();
                if ($poItemId) {
                    $itemsToRecalculate['po_item'][] = $poItemId;
                }
                if ($prItemId) {
                    $itemsToRecalculate['pr_item'][] = $prItemId;
                }
            }
        }

        foreach (array_unique($itemsToRecalculate['po_item'] ?? []) as $poItemId) {
            $poItem = PoItems::find($poItemId);
            if ($poItem) {
                $poItem->recalculateReceivedQty();
            }
        }

        foreach (array_unique($itemsToRecalculate['pr_item'] ?? []) as $prItemId) {
            $prItem = PrItem::find($prItemId);
            if ($prItem) {
                $prItem->recalculateQtyPurchase();
            }
        }
    }

    private function recalculateItemQuantities($items)
    {
        foreach ($items as $itemData) {
            if (isset($itemData['po_item'])) {
                $poItem = PoItems::find($itemData['po_item']);
                if ($poItem) {
                    $poItem->recalculateReceivedQty();
                }
            }

            if (isset($itemData['pr_item'])) {
                $prItem = PrItem::find($itemData['pr_item']);
                if ($prItem) {
                    $prItem->recalculateQtyPurchase();
                }
            }
        }
    }
}