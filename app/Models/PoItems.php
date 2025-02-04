<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PrItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

class PoItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_id',
        'pr_id',
        'pr_item_id',
        'product_id',
        'supplier_id',
        'campus',
        'division',
        'department',
        'location',
        'description',
        'qty',
        'uom',
        'currency',
        'currency_rate',
        'unit_price',
        'discount',
        'vat',
        'total_usd',
        'total_khr',
        'received_qty',
        'cancelled_qty',
        'pending',
        'purchaser_id',
        'is_cancelled',
        'cancelled_reason',
        'status'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'pr_id');
    }

    public function prItem()
    {
        return $this->belongsTo(PrItem::class, 'pr_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchaser_id');
    }

    public function cancelItem($cancelled_reason, $cancelled_qty)
    {
        $remainingQty = $this->qty - $this->cancelled_qty;

        if ($cancelled_qty > $remainingQty) {
            throw new \Exception('Cancelled quantity cannot exceed remaining quantity.');
        }

        $this->cancelled_reason = $cancelled_reason;
        $this->cancelled_qty += $cancelled_qty;
        $this->pending = $this->qty - $this->cancelled_qty;

        if ($this->cancelled_qty == $this->qty) {
            $this->status = 'Cancelled';
            $this->is_cancelled = 1;
        }

        $this->save();

        $purchaseOrder = $this->purchaseOrder;
        $allItemsCancelled = $purchaseOrder->poItems->every(function ($item) {
            return $item->status === 'Cancelled';
        });

        if ($allItemsCancelled) {
            $purchaseOrder->status = 'Cancelled';
            $purchaseOrder->is_cancelled = 1;
            $purchaseOrder->save();
        }

        return $this;
    }
}
