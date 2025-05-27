<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancellation_id',
        'purchase_order_id',
        'purchase_order_item_id',
        'purchase_request_id',
        'purchase_request_item_id',
        'cancellation_reason',
        'cancellation_by',
        'qty',
    ];

    public function cancellation()
    {
        return $this->belongsTo(Cancellation::class, 'cancellation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'cancellation_by');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PoItems::class, 'purchase_order_item_id');
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function purchaseRequestItem()
    {
        return $this->belongsTo(PrItem::class, 'purchase_request_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'purchase_request_item_id', 'id'); // Link to the product via purchase_request_item_id
    }
}
