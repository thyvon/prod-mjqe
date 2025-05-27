<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PrItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\PurchaseInvoiceItem;
use App\Models\CancellationItems;

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
        'grand_total',
        'total_usd',
        'total_khr',
        'paid_amount',
        'due_amount',
        'received_qty',
        'cancelled_qty',
        'pending',
        'purchaser_id',
        'is_cancelled',
        'force_close',
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

    public function calculateQtyCancel()
    {
        if ($this->isCalculating) {
            return;
        }
    
        $this->isCalculating = true;
        $this->cancelled_qty = CancellationItems::where('purchase_order_item_id', $this->id)->sum('qty');
    
        // Check if the cancelled quantity equals the total quantity
        if ($this->cancelled_qty == $this->qty) {
            $this->is_cancelled = 1;
        } else {
            $this->is_cancelled = 0;
        }
    
        $this->isCalculating = false;
        $this->save();
    }

    public function calculatePaidAmount()
    {
        if ($this->isCalculating) {
            return;
        }

        $this->isCalculating = true;
        $this->paid_amount = PurchaseInvoiceItem::where('po_item', $this->id)->sum('paid_amount');
        $this->isCalculating = false;
    }

    public function calculateForceClose()
    {
        if ($this->isCalculating) {
            return;
        }
        $this->isCalculating = true;
        $this->force_close = PurchaseInvoiceItem::where('po_item', $this->id)->sum('stop_purchase') > 0 ? 1 : 0;
        $this->isCalculating = false;
        $this->save();
        $this->calculateStatus(); // Ensure status is recalculated after setting force_close
    }

    public function calculateStatus()
    {
        if ($this->is_cancelled) {
            $this->status = 'Void';
        } elseif ($this->force_close == 1) {
            $this->status = 'Closed';
        } elseif ($this->received_qty == ($this->qty - $this->cancelled_qty)) {
            $this->status = 'Closed';
        } elseif ($this->received_qty < ($this->qty - $this->cancelled_qty) && $this->received_qty != 0) {
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

    public function recalculateQtyCancel()
    {
        $this->calculateQtyCancel();
        if ($this->cancelled_qty > ($this->qty - $this->received_qty)) {
            throw new \Exception('Cancelled quantity cannot exceed the remaining quantity.');
        }
        $this->calculateStatus();
        $this->save();
    }

    // public function recalculateQtyCancelValidation()
    // {
    //     $this->cancelled_qty = CancellationItems::where('purchase_order_item_id', $this->id)->sum('qty');
    
    //     if ($this->cancelled_qty > ($this->qty - $this->received_qty)) {
    //         throw new \Exception('Cancelled quantity cannot exceed the remaining quantity.');
    //     }
    // }

    public function recalculateQtyCancelValidation()
    {
        $this->cancelled_qty = CancellationItems::where('purchase_order_item_id', $this->id)
        ->sum('qty');

    
        // Log each value for debugging or tracking
        Log::info('Recalculating Cancelled Quantity Validation', [
            'purchase_order_item_id' => $this->id,
            'qty' => $this->qty,
            'qty_purchase' => $this->received_qty,
            'qty_cancel' => $this->cancelled_qty,
            'remaining_qty' => $this->qty - $this->received_qty,
        ]);
    
        if ($this->cancelled_qty > ($this->qty - $this->received_qty)) {
            throw new \Exception('Cancelled quantity cannot exceed the remaining quantity.');
        }
    }

    public function recalculatePaidAmount()
    {
        $this->calculatePaidAmount();
        if ($this->paid_amount > $this->grand_total) {
            throw new \Exception('Paid amount cannot exceed the grand total.');
        }
        $this->save();
    }

    public function calculateDueAmount()
    {
        if ($this->isCalculating) {
            return;
        }

        $this->isCalculating = true;
        $this->due_amount = $this->grand_total - $this->paid_amount;
        $this->isCalculating = false;
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->calculatePending();
            $model->calculatePaidAmount();
            $model->calculateDueAmount();
        });
    }
}
