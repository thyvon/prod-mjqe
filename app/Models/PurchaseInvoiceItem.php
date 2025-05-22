<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_invoice_items';

    protected $fillable = [
        'pi_number',
        'invoice_date',
        'payment_type',
        'invoice_no',
        'pr_number',
        'po_number', // Ensure this column stores the purchase order ID
        'pr_item',
        'po_item',
        'supplier',
        'item_code',
        'description',
        'remark',
        'qty',
        'uom',
        'currency',
        'currency_rate',
        'unit_price',
        'total_price',
        'discount',
        'service_charge',
        'deposit',
        'vat',
        'return',
        'retention',
        'total_usd',
        'total_khr',
        // 'due_amount',
        'paid_amount',
        'rounding_method',
        'rounding_digits',
        'requested_by',
        'campus',
        'division',
        'department',
        'location',
        'purchased_by',
        'purpose',
        'payment_term',
        'transaction_type',
        'cash_ref',
        'stop_purchase',
        'asset_type'
    ];

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'pi_number');
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'pr_number');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_number');
    }

    public function prItem()
    {
        return $this->belongsTo(PrItem::class, 'pr_item');
    }

    public function poItem()
    {
        return $this->belongsTo(PoItems::class, 'po_item');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_code');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function purchasedBy()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function cashRequest()
    {
        return $this->belongsTo(CashRequest::class, 'cash_ref');
    }
}
