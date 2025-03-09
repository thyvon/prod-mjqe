<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PoItems;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'currency',
        'currency_rate',
        'po_number',
        'user_id',
        'purpose',
        'supplier_id',
        'vat',
        'total_item',
        'cancelled_item',
        'purchased_item',
        'pending_item',
        'payment_term',
        'total_amount_usd',
        'total_amount_khr',
        'paid_amount',
        'due_amount',
        'is_cancelled',
        'cancelled_reason',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function poItems()
    {
        return $this->hasMany(PoItems::class, 'po_id');
    }

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function cancel($cancelled_reason)
    {
        $this->status = 'Cancelled';
        $this->is_cancelled = 1;
        $this->cancelled_reason = $cancelled_reason;
        $this->save();

        // Update cancelled_qty and pending for each item
        foreach ($this->poItems as $item) {
            $item->cancelled_qty = $item->qty;
            $item->pending = 0;
            $item->cancelled_reason = $cancelled_reason; // Copy cancelled_reason to PO item
            if ($item->cancelled_qty == $item->qty) {
                $item->is_cancelled = 1;
                $item->status = 'Cancelled';
            }
            $item->save();
        }

        $this->load(['poItems.prItem.product', 'user', 'supplier']);

        return $this;
    }

    public function updateTotalItem()
    {
        $this->total_item = $this->poItems()->count();
        $this->save();
    }

    public function updateCancelledItem()
    {
        $this->cancelled_item = $this->poItems()->whereColumn('qty', 'cancelled_qty')->count();
        $this->save();
    }

    public function updatePurchasedItem()
    {
        $this->purchased_item = $this->poItems()
            ->where('status', 'Closed')
            ->where('is_cancelled', 0)
            ->count();
        $this->save();
    }

    public function updatePendingItem()
    {
        $this->pending_item = $this->total_item - $this->cancelled_item - $this->purchased_item;
        $this->save();
    }

    public function updateStatus()
    {
        if ($this->purchased_item == ($this->total_item - $this->cancelled_item)) {
            $this->status = 'Closed';
        } elseif ($this->cancelled_item == $this->total_item) {
            $this->status = 'Void';
        } elseif (($this->total_item - $this->cancelled_item - $this->purchased_item) != 0 && $this->purchased_item != 0) {
            $this->status = 'Partial';
        } else {
            $this->status = 'Pending';
        }
        $this->save();
    }
}
