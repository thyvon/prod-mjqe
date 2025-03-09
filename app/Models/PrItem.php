<?php

// app/Models/PrItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseRequest;
use App\Models\Product;
use App\Models\PoItem;
use App\Models\PurchaseInvoiceItem;

class PrItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'product_id',
        'remark',
        'qty',
        'uom',
        'unit_price',
        'total_price',
        'campus',
        'division',
        'department',
        'qty_cancel',
        'qty_po',
        'qty_purchase',
        'qty_pending',
        'status',
        'force_close',
        'is_cancel',
        'reason',
    ];

    protected $isCalculating = false;

    protected static function booted()
    {
        static::created(function ($prItem) {
            $prItem->purchaseRequest->updateTotalItem();
            $prItem->purchaseRequest->updateCancelledItem();
            $prItem->purchaseRequest->updatePurchasedItem();
            $prItem->purchaseRequest->updatePendingItem();
            $prItem->purchaseRequest->updateStatus();
        });

        static::updated(function ($prItem) {
            $prItem->purchaseRequest->updateTotalItem();
            $prItem->purchaseRequest->updateCancelledItem();
            $prItem->purchaseRequest->updatePurchasedItem();
            $prItem->purchaseRequest->updatePendingItem();
            $prItem->purchaseRequest->updateStatus();
        });

        static::deleted(function ($prItem) {
            $prItem->purchaseRequest->updateTotalItem();
            $prItem->purchaseRequest->updateCancelledItem();
            $prItem->purchaseRequest->updatePurchasedItem();
            $prItem->purchaseRequest->updatePendingItem();
            $prItem->purchaseRequest->updateStatus();
        });
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function poItems()
    {
        return $this->hasMany(PoItems::class, 'pr_item_id');
    }

    public function invoiceItem()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'pr_item');
    }

    public function calculateQtyPurchase()
    {
        if ($this->isCalculating) {
            return;
        }

        $this->isCalculating = true;
        $this->qty_purchase = PurchaseInvoiceItem::where('pr_item', $this->id)->sum('qty');
        $this->isCalculating = false;
    }

    public function calculatePending()
    {
        if ($this->isCalculating) {
            return;
        }

        $this->isCalculating = true;
        $this->qty_pending = $this->qty - $this->qty_cancel - $this->qty_purchase;
        $this->isCalculating = false;
    }

    public function calculateForceClose()
    {
        if ($this->isCalculating) {
            return;
        }
        $this->isCalculating = true;
        $this->force_close = PurchaseInvoiceItem::where('pr_item', $this->id)->sum('stop_purchase') > 0 ? 1 : 0;
        $this->isCalculating = false;
        $this->save();
        $this->calculateStatus(); // Ensure status is recalculated after setting force_close
    }

    public function calculateStatus()
    {
        if ($this->force_close == 1) {
            $this->status = 'Closed';
        } elseif ($this->is_cancel == 1) {
            $this->status = 'Void';
        } elseif ($this->qty_purchase == ($this->qty - $this->qty_cancel)) {
            $this->status = 'Closed';
        } elseif ($this->qty_purchase < ($this->qty - $this->qty_cancel)) {
            $this->status = 'Partial';
        } else {
            $this->status = 'Pending';
        }
        $this->save();
    }

    public function recalculateQtyPurchase()
    {
        $this->calculateQtyPurchase();
        if ($this->qty_purchase > ($this->qty - $this->qty_cancel)) {
            throw new \Exception('Received quantity cannot exceed the remaining quantity.');
        }
        $this->calculateStatus();
        $this->save();
    }

    public function performCalculations()
    {
        if ($this->isCalculating) {
            return;
        }

        $this->isCalculating = true;

        $this->calculateQtyPurchase();
        $this->calculatePending();
        $this->recalculateQtyPurchase();
        $this->calculateStatus();

        $this->isCalculating = false;
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