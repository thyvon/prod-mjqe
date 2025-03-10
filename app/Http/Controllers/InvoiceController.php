<?php

namespace App\Http\Controllers;

use App\Models\{PurchaseInvoice, PurchaseInvoiceItem, User, PrItem, PoItems, CashRequest, PurchaseRequest, PurchaseOrder, Supplier, InvoiceAttachment};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Services\LocalFileService; // Update to use LocalFileService

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

            // Recalculate quantities and amounts for PO and PR items
            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['po_item'])) {
                    $poItem = PoItems::find($itemData['po_item']);
                    if ($poItem) {
                        $poItem->recalculateReceivedQty();
                        $poItem->recalculatePaidAmount();
                        $poItem->calculateForceClose();
                    }
                }

                if (isset($itemData['pr_item'])) {
                    $prItem = PrItem::find($itemData['pr_item']);
                    if ($prItem) {
                        $prItem->recalculateQtyPurchase();
                        $prItem->calculateForceClose();
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
        try {
            $invoice = PurchaseInvoice::with(['items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier'])->findOrFail($id);
            return response()->json([
                'invoice' => $invoice,
                'vat_rate' => $invoice->vat_rate,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in edit method:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error fetching invoice.'], 500);
        }
    }

    public function show($id)
    {
        $invoice = PurchaseInvoice::with(['items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier', 'attachments'])->findOrFail($id);
        return response()->json($invoice);
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
                    $poItem->recalculatePaidAmount();
                    $poItem->calculateForceClose();
                }
            }

            if ($item->pr_item) {
                $prItem = PrItem::find($item->pr_item);
                if ($prItem) {
                    $prItem->recalculateQtyPurchase();
                    $prItem->calculateForceClose();
                }
            }
        }

        // Delete associated attachments
        $attachments = $invoice->attachments;
        foreach ($attachments as $attachment) {
            $localFileService = new LocalFileService();
            $localFileService->deleteFile($attachment->file_url);
            $attachment->delete();
        }

        $invoice->items()->delete();
        $invoice->delete();

        foreach ($invoiceItems as $item) {
            if ($item->po_item) {
                $poItem = PoItems::find($item->po_item);
                if ($poItem) {
                    $poItem->recalculateReceivedQty();
                    $poItem->recalculatePaidAmount();
                    $poItem->calculateForceClose();
                }
            }

            if ($item->pr_item) {
                $prItem = PrItem::find($item->pr_item);
                if ($prItem) {
                    $prItem->recalculateQtyPurchase();
                    $prItem->calculateForceClose();
                }
            }
        }

        return response()->json(null, 204);
    }

    public function itemList()
    {
        return Inertia::render('Purchase/Invoices/ItemList', [
            'invoiceItems' => PurchaseInvoiceItem::with([
                'purchaseRequest:id,pr_number',
                'purchaseOrder:id,po_number',
                'product:id,sku,product_description',
                'supplier:id,name',
                'invoice:id,pi_number'
            ])->get(),
            'suppliers' => Supplier::all(),
            'vatRate' => config('app.vat_rate', 0),
        ]);
    }

    public function forceClose($id)
    {
        $invoiceItem = PurchaseInvoiceItem::findOrFail($id); // Fix class reference
        $invoiceItem->stop_purchase = 1;
        $invoiceItem->save();

        if ($invoiceItem->po_item) {
            $poItem = PoItems::find($invoiceItem->po_item);
            if ($poItem) {
                $poItem->calculateForceClose();
            }
        }

        if ($invoiceItem->pr_item) {
            $prItem = PrItem::find($invoiceItem->pr_item);
            if ($prItem) {
                $prItem->calculateForceClose();
            }
        }

        return response()->json(['message' => 'Invoice item force closed successfully.']);
    }

    public function filterInvoiceItems(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $supplierId = $request->input('supplier_id');
        $transactionType = $request->input('transaction_type');

        $query = PurchaseInvoiceItem::with([
            'purchaseRequest:id,pr_number',
            'purchaseOrder:id,po_number',
            'product:id,sku,product_description',
            'supplier:id,name',
            'invoice:id,pi_number'
        ]);

        if ($startDate && $endDate) {
            $query->whereBetween('invoice_date', [$startDate, $endDate]);
        }

        if ($supplierId) {
            $query->where('supplier', $supplierId);
        }

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        $filteredItems = $query->get();

        return response()->json($filteredItems);
    }

    public function searchSuppliers(Request $request)
    {
        $query = $request->input('q');
        $suppliers = Supplier::where('name', 'like', '%' . $query . '%')->get();
        return response()->json($suppliers);
    }

    public function filterCashRequests(Request $request)
    {
        $transactionType = $request->input('transaction_type');
        $cashRequests = CashRequest::where('request_type', $transactionType)
                                   ->where('status', 0)
                                   ->get();
        
        Log::info('Filtered Cash Requests:', ['transaction_type' => $transactionType, 'cashRequests' => $cashRequests]);

        return response()->json($cashRequests);
    }

    public function attachFile(Request $request, $id)
    {
        try {
            $invoice = PurchaseInvoice::findOrFail($id);
            $file = $request->file('file');
            $localFileService = new LocalFileService();
            $fileIndex = InvoiceAttachment::where('purchase_invoice_id', $id)->count() + 1;
            $fileUrl = $localFileService->uploadFile($file, $invoice->pi_number, $fileIndex);

            if (!$fileUrl) {
                throw new \Exception('File upload failed, no URL returned.');
            }

            $attachment = new InvoiceAttachment();
            $attachment->purchase_invoice_id = $invoice->id; // Ensure the correct column name is used
            $attachment->file_url = $fileUrl;
            //$attachment->file_name = $file->getClientOriginalName(); // Ensure file name is saved
            //$attachment->file_size = $file->getSize(); // Ensure file size is saved
            $attachment->save();

            return response()->json(['message' => 'File attached successfully', 'attachment' => $attachment], 201);
        } catch (\Exception $e) {
            Log::error('Error attaching file', ['exception' => $e, 'request_data' => $request->all(), 'stack_trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function deleteFile($id)
    {
        try {
            $attachment = InvoiceAttachment::findOrFail($id);
            $localFileService = new LocalFileService();
            $localFileService->deleteFile($attachment->file_url);
            $attachment->delete();

            return response()->json(['message' => 'File deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting file', ['exception' => $e, 'stack_trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function updateFile(Request $request, $id)
    {
        try {
            $attachment = InvoiceAttachment::findOrFail($id);
            $file = $request->file('file');
            $localFileService = new LocalFileService();
            $localFileService->deleteFile($attachment->file_url);
            $fileUrl = $localFileService->uploadFile($file);

            $attachment->file_url = $fileUrl;
            $attachment->save();

            return response()->json(['message' => 'File updated successfully', 'attachment' => $attachment], 200);
        } catch (\Exception $e) {
            Log::error('Error updating file', ['exception' => $e, 'request_data' => $request->all(), 'stack_trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
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
            'vat_rate' => 'nullable|numeric',
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
            'items.*.service_charge' => 'nullable|numeric',
            'items.*.deposit' => 'nullable|numeric',
            'items.*.vat' => 'nullable|numeric',
            'items.*.return' => 'nullable|numeric',
            'items.*.retention' => 'nullable|numeric',
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
            'items.*.deposit' => 'nullable|numeric',
        ];

        if ($transactionType != 2) {
            $rules['cash_ref'] = 'required|integer|exists:cash_requests,id';
        }

        return $rules;
    }

    private function validateItemQuantities($items, $invoiceId = null)
    {
        $itemQuantities = [];
        $itemPaidAmounts = [];

        foreach ($items as $itemData) {
            if (isset($itemData['po_item'])) {
                $itemQuantities['po_item'][$itemData['po_item']] = ($itemQuantities['po_item'][$itemData['po_item']] ?? 0) + $itemData['qty'];
                $itemPaidAmounts['po_item'][$itemData['po_item']] = ($itemPaidAmounts['po_item'][$itemData['po_item']] ?? 0) + $itemData['paid_amount'];
            }

            if (isset($itemData['pr_item'])) {
                $itemQuantities['pr_item'][$itemData['pr_item']] = ($itemQuantities['pr_item'][$itemData['pr_item']] ?? 0) + $itemData['qty'];
            }
        }

        foreach ($items as $index => $itemData) {
            if (isset($itemData['po_item'])) {
                $poItem = PoItems::find($itemData['po_item']);
                $poQty = $poItem->qty - $poItem->cancelled_qty;
                $receivedQty = PurchaseInvoiceItem::where('po_item', $poItem->id)
                    ->when($invoiceId, fn($query) => $query->where('pi_number', '!=', $invoiceId))
                    ->sum('qty');
                $pendingQty = $poQty - $receivedQty;

                if ($itemQuantities['po_item'][$itemData['po_item']] > $pendingQty) {
                    $sku = $poItem->product->sku ?? 'unknown';
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$index.qty" => ['The qty of Item: ' . $sku . ' cannot exceed the pending qty in PO.']
                    ]);
                }

                $paidAmount = PurchaseInvoiceItem::where('po_item', $poItem->id)
                    ->when($invoiceId, fn($query) => $query->where('pi_number', '!=', $invoiceId))
                    ->sum('paid_amount');
                $remainingDueAmount = $poItem->grand_total - $paidAmount;

                if ($itemPaidAmounts['po_item'][$itemData['po_item']] > $remainingDueAmount) {
                    $sku = $poItem->product->sku ?? 'unknown';
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$index.paid_amount" => ['The paid amount of Item: ' . $sku . ' cannot exceed the due amount (' . $remainingDueAmount . ') in PO.']
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
                    $sku = $prItem->product->sku ?? 'unknown';
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$index.qty" => ['The qty of Item: ' . $sku . ' cannot exceed the pending qty in PR.']
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

        $totalPriceSum = array_reduce($items, function ($sum, $item) {
            return $sum + ($item['qty'] * $item['unit_price']);
        }, 0);

        $rateDiscount = $totalPriceSum > 0 ? $invoice->discount_total / $totalPriceSum : 0;

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
            $itemData['currency'] = $invoice->currency;

            $itemData['total_price'] = $itemData['qty'] * $itemData['unit_price'];

            if ($itemData['currency'] == 1) {
                $itemData['total_usd'] = $itemData['paid_amount'];
            } elseif ($itemData['currency'] == 2) {
                $itemData['total_usd'] = $itemData['paid_amount'] / $itemData['currency_rate'];
            }

            if ($itemData['currency'] == 1) {
                $itemData['total_khr'] = 0;
            } elseif ($itemData['currency'] == 2) {
                $itemData['total_khr'] = $itemData['paid_amount'];
            }

            if ($invoice->service_charge != 0 && $invoice->service_charge != '') {
                $itemData['service_charge'] = floatval($serviceChargePerItem);
            } else {
                $itemData['service_charge'] = $itemData['service_charge'] ?? 0;
            }

            if ($invoice->discount_total != 0 && $invoice->discount_total != '') {
                $itemData['discount'] = $itemData['total_price'] * $rateDiscount;
            } else {
                $itemData['discount'] = $itemData['discount'] ?? 0;
            }

            $itemData['discount_total'] = $invoice->discount_total;

            $itemData['deposit'] = $itemData['deposit'] ?? 0;

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
                $poItem->recalculatePaidAmount();
                $poItem->calculateForceClose();
            }
        }

        foreach (array_unique($itemsToRecalculate['pr_item'] ?? []) as $prItemId) {
            $prItem = PrItem::find($prItemId);
            if ($prItem) {
                $prItem->recalculateQtyPurchase();
                $prItem->calculateForceClose();
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
                    $poItem->recalculatePaidAmount();
                    $poItem->calculateForceClose();
                }
            }

            if (isset($itemData['pr_item'])) {
                $prItem = PrItem::find($itemData['pr_item']);
                if ($prItem) {
                    $prItem->recalculateQtyPurchase();
                    $prItem->calculateForceClose();
                }
            }
        }
    }
}