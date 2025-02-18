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
use App\Models\PurchaseInvoiceItem;

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

    protected $isCalculating = false;

    protected static function booted()
    {
        static::created(function ($poItem) {
            $poItem->purchaseOrder->updateTotalItem();
            $poItem->purchaseOrder->updateCancelledItem();
            $poItem->purchaseOrder->updatePurchasedItem();
            $poItem->purchaseOrder->updatePendingItem();
            $poItem->purchaseOrder->updateStatus();
        });

        static::updated(function ($poItem) {
            $poItem->purchaseOrder->updateTotalItem();
            $poItem->purchaseOrder->updateCancelledItem();
            $poItem->purchaseOrder->updatePurchasedItem();
            $poItem->purchaseOrder->updatePendingItem();
            $poItem->purchaseOrder->updateStatus();
        });

        static::deleted(function ($poItem) {
            $poItem->purchaseOrder->updateTotalItem();
            $poItem->purchaseOrder->updateCancelledItem();
            $poItem->purchaseOrder->updatePurchasedItem();
            $poItem->purchaseOrder->updatePendingItem();
            $poItem->purchaseOrder->updateStatus();
        });
    }

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
            $this->status = 'Void';
            $this->is_cancelled = 1;
        }

        $this->save();

        $this->calculatePending();

        $purchaseOrder = $this->purchaseOrder;
        $allItemsCancelled = $purchaseOrder->poItems->every(function ($item) {
            return $item->status === 'Void';
        });

        if ($allItemsCancelled) {
            $purchaseOrder->status = 'Void';
            $purchaseOrder->is_cancelled = 1;
            $purchaseOrder->save();
        }

        return $this;
    }

    public function calculatePending()
    {
        if ($this->isCalculating) {
            return;
        }

        $this->isCalculating = true;
        $this->pending = $this->qty - $this->cancelled_qty - $this->received_qty;
        $this->isCalculating = false;
    }

    public function calculateReceivedQty()
    {
        if ($this->isCalculating) {
            return;
        }

        $this->isCalculating = true;
        $this->received_qty = PurchaseInvoiceItem::where('po_item', $this->id)->sum('qty');
        $this->isCalculating = false;
    }

    public function calculateStatus()
    {
        if ($this->is_cancelled) {
            $this->status = 'Void';
        } elseif ($this->received_qty == ($this->qty - $this->cancelled_qty)) {
            $this->status = 'Closed';
        } elseif ($this->received_qty < ($this->qty - $this->cancelled_qty)) {
            $this->status = 'Partial';
        } else {
            $this->status = 'Pending';
        }
        $this->save();
    }

    public function recalculateReceivedQty()
    {
        $this->calculateReceivedQty();
        if ($this->received_qty > ($this->qty - $this->cancelled_qty)) {
            throw new \Exception('Received quantity cannot exceed the remaining quantity.');
        }
        $this->calculateStatus();
        $this->save();
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->calculatePending();
        });
    }
}
