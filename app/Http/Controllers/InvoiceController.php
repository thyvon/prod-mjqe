<?php

namespace App\Http\Controllers;

use App\Models\{PurchaseInvoice, PurchaseInvoiceItem, User, PrItem, PoItems, CashRequest, PurchaseRequest, PurchaseOrder, Supplier, InvoiceAttachment, Product};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Services\SharePointService;
use App\Services\LocalFileService; // Update to use LocalFileService

class InvoiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Purchase/Invoices/Index', [
            'purchaseInvoices' => PurchaseInvoice::with([
                'items:id,pi_number,pi_number',
                'supplier:id,name,vat,currency',
                'cashRequest:id,ref_no',
            ])
            ->select([
                'id', 'pi_number', 'invoice_date', 'total_amount',
                'paid_amount', 'paid_usd', 'currency', 'currency_rate',
                'transaction_type', 'payment_type', 'status', 'supplier', 'cash_ref' // Include foreign key
            ])
            ->get(),
            'users' => User::select('id', 'name')->get(),
            'prItems' => PrItem::with(['product:id,product_description,sku,price', 'purchaseRequest:id,pr_number,purpose'])
            ->select('id', 'product_id', 'purchase_request_id', 'qty', 'uom', 'unit_price', 'total_price')
            ->get(),
            'poItems' => PoItems::with(['product:id,product_description,sku,price', 'purchaseOrder:id,po_number,purpose', 'purchaseRequest:id,pr_number'])
            ->select('id', 'product_id', 'po_id', 'qty', 'uom', 'unit_price')
            ->get(),
            'cashRequests' => CashRequest::select('id','ref_no')->get(),
            'purchaseRequests' => PurchaseRequest::select('id', 'pr_number')->get(),
            'purchaseOrders' => PurchaseOrder::select('id', 'po_number')->get(),
            'suppliers' => Supplier::select('id', 'name')->get(),
            'currentUser' => auth()->user()->only(['id', 'name']),

        ]);
    }

    public function getPrItems(Request $request)
    {
        return response()->json(
            PrItem::with(['product:id,product_description,sku,price', 'purchaseRequest:id,pr_number,purpose'])
                ->whereHas('purchaseRequest', fn($query) => $query->where('pr_number', $request->query('pr_number')))
                ->get()
        );
    }

    public function getPoItems(Request $request)
    {
        return response()->json(
            PoItems::with(['product:id,product_description,sku,price', 'purchaseOrder:id,po_number,purpose', 'purchaseRequest:id,pr_number'])
                ->whereHas('purchaseOrder', fn($query) => $query->where('po_number', $request->query('po_number')))
                ->get()
        );
    }

    public function store(Request $request)
    {
        try {

            $rules = $this->getValidationRules($request->transaction_type);
            $validatedData = $request->validate($rules);

            // if ($validatedData['transaction_type'] == 2) {
            //     $validatedData['cash_ref'] = null;
            // }

            $this->validateItemQuantities($validatedData['items']);

            $this->validateCashAmount($validatedData['cash_ref'], $validatedData['items']);

            // Use the purchased_by value from the request
            $validatedData['purchased_by'] = $request->input('purchased_by') ?? auth()->id();
            $validatedData['created_by'] = auth()->id();

            $invoice = PurchaseInvoice::create($validatedData);
            Log::info('Invoice created', ['invoice' => $invoice->toArray()]);

            $this->createOrUpdateInvoiceItems($invoice, $validatedData['items']);
            $this->recalculateItemQuantities($validatedData['items']); // Recalculate item quantities after creation

            // Calculate and update the paid_usd field
            $invoice->paid_usd = $invoice->items()->sum('total_usd');
            $invoice->save();

            // Fetch updated invoices
            $updatedInvoices = PurchaseInvoice::with(['items', 'supplier', 'cashRequest'])->get();

            // Return the invoice with related data for consistency
            return response()->json([
                'message' => 'Invoice created successfully.',
                'purchaseInvoices' => $updatedInvoices, // Return updated invoices
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in store method', ['errors' => $e->errors(), 'request_data' => $request->all()]);
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in store method', [
                'exception' => $e->getMessage(),
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $invoice = PurchaseInvoice::with(['items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier', 'attachments'])->findOrFail($id);

            // Ensure purchased_by is included in the response
            return response()->json([
                'invoice' => $invoice,
                'transaction_type' => $invoice->transaction_type,
                'cash_ref' => $invoice->cash_ref, // Include cash_ref explicitly
                'vat_rate' => $invoice->vat_rate,
                'purchased_by' => $invoice->purchased_by, // Include purchased_by explicitly
            ]);
        } catch (\Exception $e) {
            Log::error('Error in edit method:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error fetching invoice.'], 500);
        }
    }

    public function show($id)
    {
        try {
            $invoice = PurchaseInvoice::with(['items.purchaseRequest:id,pr_number', 'items.purchaseOrder:id,po_number', 'items.product:id,product_description,sku', 'supplier:id,name,number,address', 'attachments:id,purchase_invoice_id,file_url,file_name,sharepoint_file_id','purchasedBy:id,name'])->findOrFail($id);
            return Inertia::render('Purchase/Invoices/Show', [
                'invoice' => $invoice,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in show method:', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error fetching invoice details.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Cash Reference ID:', ['cash_ref' => $request->input('cash_ref')]); // Log the cash_ref value

            $rules = $this->getValidationRules($request->transaction_type);
            $validatedData = $request->validate($rules);

            

            $invoice = PurchaseInvoice::findOrFail($id);
            $existingItems = $invoice->items->keyBy('id');

            // if ($validatedData['transaction_type'] == 2) {
            //     $validatedData['cash_ref'] = null; // Ensure cash_ref is null for Credit transactions
            // } else {
            //     $validatedData['cash_ref'] = $request->input('cash_ref'); // Retain cash_ref for other transaction types
            // }

            // Log::info('Cash Reference ID:', ['cash_ref' => $validatedData['cash_ref']]); // Log the cash_ref value

            $this->validateItemQuantities($validatedData['items'], $invoice->id);

            $this->validateCashAmount($validatedData['cash_ref'], $validatedData['items'], $invoice->id);


            // Use the purchased_by value from the request
            $validatedData['purchased_by'] = $request->input('purchased_by');
            $validatedData['created_by'] = auth()->id();

            $invoice->update($validatedData);

            $updatedItemIds = $this->createOrUpdateInvoiceItems($invoice, $validatedData['items'], $existingItems);

            $this->deleteRemovedItems($existingItems, $updatedItemIds);

            $this->recalculateItemQuantities($validatedData['items']);

            // Calculate and update the paid_usd field
            $invoice->paid_usd = $invoice->items()->sum('total_usd');
            $invoice->save();

            // Fetch updated invoices
            $updatedInvoices = PurchaseInvoice::with(['items', 'supplier', 'cashRequest'])->get();

            // Ensure cash_ref is included in the response
            return response()->json([
                'message' => 'Invoice updated successfully.',
                'purchaseInvoices' => $updatedInvoices, // Return updated invoices
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in update method', ['errors' => $e->errors(), 'request_data' => $request->all()]);
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in update method', [
                'exception' => $e->getMessage(),
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);
        $invoiceItems = $invoice->items;

        // Collect item codes for product price updates
        $deletedItemCodes = [];
        foreach ($invoiceItems as $item) {
            if ($item->item_code) {
                $deletedItemCodes[] = $item->item_code;
            }
        }

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
        $sharePointService = new SharePointService();
        foreach ($attachments as $attachment) {
            // Delete the file from SharePoint
            if ($attachment->sharepoint_file_id) {
                $sharePointService->deleteFileById($attachment->sharepoint_file_id);
            } else {
                $sharePointService->deleteFileByPath($attachment->file_name);
            }

            // Delete the attachment record from the database
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

        // Perform product price updates after the invoice and items are deleted
        foreach (array_unique($deletedItemCodes) as $itemCode) {
            $product = Product::find($itemCode);
            if ($product) {
                $product->updatePriceFromLatestPurchase();
                $product->calculateAveragePriceLastThreeMonths();
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

    public function searchPurchaser(Request $request)
    {
        $query = $request->input('q');
        $purchasers = User::where('name', 'like', '%' . $query . '%')
            ->select('id', 'name') // Select only necessary fields for Select2
            ->get();

        return response()->json($purchasers);
    }

    public function filterCashRequests(Request $request)
    {
        $transactionType = $request->input('transaction_type');
    
        if (!in_array($transactionType, [1, 2, 3])) {
            return response()->json(['error' => 'Invalid transaction type'], 400);
        }
    
        $cashRequests = CashRequest::where('approval_status', 4)
            ->when($transactionType == 1, fn($query) => $query->where('request_type', 1))
            ->when($transactionType == 3, fn($query) => $query->where('request_type', 2))
            ->when($transactionType == 2, fn($query) => $query->whereNull('request_type'))
            ->get()
            ->map(function ($cashRequest) {
                $paidAmount = PurchaseInvoiceItem::where('cash_ref', $cashRequest->id)->sum('paid_amount');
                $remainingAmount = $cashRequest->amount - $paidAmount;
                $currency = match ($cashRequest->currency ?? 1) {
                    1 => 'USD',
                    2 => 'KHR',
                    default => '',
                };
    
                return [
                    'id' => $cashRequest->id,
                    'amount' => $cashRequest->amount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'request_type' => $cashRequest->request_type,
                    'approval_status' => $cashRequest->approval_status,
                    'currency' => $currency,
                    'label' => $cashRequest->ref_no . ' | (' . number_format($remainingAmount, 2) . ' ' . $currency . ')',
                ];
            });
    
        Log::info('Filtered Cash Requests:', [
            'transaction_type' => $transactionType,
            'cashRequests' => $cashRequests
        ]);
    
        return response()->json($cashRequests);
    }
    

    public function attachFile(Request $request, $id)
    {
        try {
            $invoice = PurchaseInvoice::with(['SupplierName', 'purchasedBy'])->findOrFail($id); // Ensure relationships are loaded
            $file = $request->file('file');
            $sharePointService = new SharePointService();

            // Safely access the supplier name
            $supplierName = $invoice->SupplierName && is_object($invoice->SupplierName) && isset($invoice->SupplierName->name)
                ? $invoice->SupplierName->name
                : 'Unknown Supplier';

            // Safely access the purchaser name
            $purchaserName = $invoice->purchasedBy && is_object($invoice->purchasedBy) && isset($invoice->purchasedBy->name)
                ? $invoice->purchasedBy->name
                : 'Unknown Purchaser';

            // Upload the file to SharePoint with dynamic metadata
            $uploadResult = $sharePointService->uploadFile(
                $file,
                $invoice->pi_number,
                $purchaserName, // Pass purchaser name dynamically
                $supplierName // Pass supplier name dynamically
            );

            if (!$uploadResult || !isset($uploadResult['sharepoint_web_url'])) {
                throw new \Exception('File upload to SharePoint failed.');
            }

            // Save the attachment details in the database
            $attachment = new InvoiceAttachment();
            $attachment->purchase_invoice_id = $invoice->id;
            $attachment->file_url = $uploadResult['sharepoint_web_url'];
            $attachment->file_name = $uploadResult['fileName'];
            $attachment->sharepoint_file_id = $uploadResult['sharepoint_file_id']; // Save SharePoint file ID
            $attachment->save();

            return response()->json(['message' => 'File attached successfully', 'attachment' => $attachment], 201);
        } catch (\Exception $e) {
            Log::error('Error attaching file', [
                'exception' => $e->getMessage(),
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    
    public function deleteFile($id)
    {
        try {
            $attachment = InvoiceAttachment::findOrFail($id);
            $sharePointService = new SharePointService();
    
            if ($attachment->sharepoint_file_id) {
                $sharePointService->deleteFileById($attachment->sharepoint_file_id);
            } else {
                $sharePointService->deleteFileByPath($attachment->file_name);
            }
    
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
            $sharePointService = new SharePointService();

            // Delete the old file from SharePoint
            if ($attachment->sharepoint_file_id) {
                $sharePointService->deleteFileById($attachment->sharepoint_file_id);
            }

            // Upload the new file to SharePoint
            $uploadResult = $sharePointService->uploadFile($file, $attachment->purchaseInvoice->pi_number);

            if (!$uploadResult || !isset($uploadResult['sharepoint_web_url'])) {
                throw new \Exception('File upload to SharePoint failed.');
            }

            // Update the attachment details in the database
            $attachment->file_url = $uploadResult['sharepoint_web_url'];
            $attachment->file_name = $uploadResult['fileName'];
            $attachment->sharepoint_file_id = $uploadResult['sharepoint_file_id']; // Update SharePoint file ID
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
            'purchased_by' => 'required|integer|exists:users,id', // Ensure purchased_by is required and valid
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
            'items.*.rounding_method' => 'nullable|string',
            'items.*.rounding_digits' => 'nullable|integer',
            'items.*.requested_by' => 'nullable|integer|exists:users,id',
            'items.*.campus' => 'required|string',
            'items.*.division' => 'required|string',
            'items.*.department' => 'required|string',
            'items.*.location' => 'required|string',
            'items.*.purpose' => 'required|string',
            'items.*.payment_term' => 'required|integer',
            'items.*.cash_ref' => 'nullable|integer|exists:cash_requests,id',
            'items.*.stop_purchase' => 'nullable|boolean',
            'items.*.asset_type' => 'nullable|integer',
            'items.*.total_usd' => 'required|numeric',
            'items.*.total_khr' => 'required|numeric',
            'items.*.deposit' => 'nullable|numeric',
            'items.*.transaction_type' => 'required|integer',
            
            // 'items.*.purchased_by' => 'required|integer|exists:users,id', // Ensure purchased_by is set for each item
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
        // Validate PO items
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

    private function validateCashAmount($cashRef, $items, $invoiceId = null)
    {
        if ($cashRef) {
            // Calculate cash amount from the database for this cash_ref
            $cashAmount = CashRequest::where('id', $cashRef)->sum('amount');
    
            // Calculate paid amount from existing invoices excluding the current invoice
            $paidAmount = PurchaseInvoiceItem::where('cash_ref', $cashRef)
                ->when($invoiceId, fn($query) => $query->where('pi_number', '!=', $invoiceId))
                ->sum('paid_amount');
    
            // Calculate remaining amount
            $remainAmount = $cashAmount - $paidAmount;

            Log::info('Form Cash Ref:', ['cash_ref' => $cashRef]);
    
            // Calculate new paid amount for this specific cash_ref
            $newPaidAmount = collect($items)->reduce(function ($sum, $item) {
                return $sum + ($item['paid_amount'] ?? 0);
            }, 0);
    
            // Validate that the total paid amount does not exceed the cash amount
            $validationAmount = $remainAmount - $newPaidAmount;
            Log::info('Validation Amount:', ['validation_amount' => $validationAmount]);
            if ($validationAmount < 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'paid_amount' => [
                        'The total paid amount for the selected cash reference exceeds the available cash amount. Remaining amount: ' . $remainAmount
                    ]
                ]);
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
                $itemData['requested_by'] = $prItem->purchaseRequest->request_by; // Use relationship to get requested_by
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
            $itemData['currency'] = $invoice->currency;
            $itemData['cash_ref'] = $invoice->cash_ref;
            $itemData['purchased_by'] = $invoice->purchased_by;

            $itemData['total_price'] = $itemData['qty'] * $itemData['unit_price'];

            // Calculate VAT based on payment_type
            if ($invoice->payment_type == 2) {
                $itemData['vat'] = $itemData['deposit'] * ($invoice->vat_rate / 100);
            } else {
                $itemData['vat'] = ($itemData['total_price'] - $itemData['discount']) * ($invoice->vat_rate / 100);
            }

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
            $itemData['rounding_method'] = $itemData['rounding_method'] ?? '';
            $itemData['rounding_digits'] = $itemData['rounding_digits'] ?? 0;

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
        $deletedItemCodes = [];
    
        foreach ($existingItems as $itemId => $item) {
            if (!in_array($itemId, $updatedItemIds)) {
                $poItemId = $item->po_item;
                $prItemId = $item->pr_item;
    
                // Collect item codes for product price updates after deletion
                if ($item->item_code) {
                    $deletedItemCodes[] = $item->item_code;
                }
    
                $item->delete();
    
                if ($poItemId) {
                    $itemsToRecalculate['po_item'][] = $poItemId;
                }
                if ($prItemId) {
                    $itemsToRecalculate['pr_item'][] = $prItemId;
                }
            }
        }
    
        // Perform product price updates after items are deleted
        foreach (array_unique($deletedItemCodes) as $itemCode) {
            $product = Product::find($itemCode);
            if ($product) {
                $product->updatePriceFromLatestPurchase();
                $product->calculateAveragePriceLastThreeMonths();
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
        $updatedItemCodes = [];
    
        foreach ($items as $itemData) {
            if (isset($itemData['po_item'])) {
                $poItem = PoItems::find($itemData['po_item']);
                if ($poItem) {
                    $poItem->recalculateReceivedQty();
                    $poItem->recalculatePaidAmount();
                    $poItem->calculateForceClose();
    
                    // Collect item codes for product price updates
                    if ($poItem->product_id) {
                        $updatedItemCodes[] = $poItem->product_id;
                    }
                }
            }
    
            if (isset($itemData['pr_item'])) {
                $prItem = PrItem::find($itemData['pr_item']);
                if ($prItem) {
                    $prItem->recalculateQtyPurchase();
                    $prItem->calculateForceClose();
    
                    // Collect item codes for product price updates
                    if ($prItem->product_id) {
                        $updatedItemCodes[] = $prItem->product_id;
                    }
                }
            }
        }
    
        // Perform product price updates after recalculations
        foreach (array_unique($updatedItemCodes) as $itemCode) {
            $product = Product::find($itemCode);
            if ($product) {
                $product->updatePriceFromLatestPurchase();
                $product->calculateAveragePriceLastThreeMonths();
            }
        }
    }

    public function print(PurchaseInvoice $invoice) // Fix class reference
    {
        return Inertia::render('Purchase/Invoices/Print', [
            'invoice' => $invoice->load('items.purchaseRequest', 'items.purchaseOrder', 'items.product', 'supplier', 'attachments'),
        ]);
    }
}