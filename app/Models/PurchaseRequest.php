<?php

// app/Models/PurchaseRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PrItem;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_number',
        'request_date',
        'request_by',
        'campus',
        'division',
        'department',
        'purpose',
        'is_urgent',
        'status',
        'total_amount',
        'total_item',
        'is_cancel',
        'item_po',
        'item_cancel',
        'item_purchas',
        'item_pending',
    ];

    public function prItems()
    {
        return $this->hasMany(PrItem::class, 'purchase_request_id');
    }

    public function requestBy()
    {
        return $this->belongsTo(User::class, 'request_by');
    }

    public function poItems()
    {
        return $this->hasMany(PoItems::class, 'pr_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($purchaseRequest) {
            $purchaseRequest->pr_number = self::generatePrNumber($purchaseRequest->campus);
        });
    }

    public static function generatePrNumber($campus)
    {
        $date = now()->format('Ymd');
        $campus = strtoupper($campus);
        $count = self::whereDate('created_at', today())
                     ->where('campus', $campus)
                     ->count() + 1;
        $pr_number = "PR-{$campus}-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);

        while (self::where('pr_number', $pr_number)->exists()) {
            $count++;
            $pr_number = "PR-{$campus}-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        return $pr_number;
    }

    public function updateTotalItem()
    {
        $this->total_item = $this->prItems()->count();
        $this->save();
    }

    public function updateCancelledItem()
    {
        $this->item_cancel = $this->prItems()->whereColumn('qty', 'qty_cancel')->count();
        $this->save();
    }

    public function updatePurchasedItem()
    {
        $this->item_purchas = $this->prItems()
            ->where('status', 'Closed')
            ->where('is_cancel', 0)
            ->count();
        $this->save();
    }

    public function updatePendingItem()
    {
        $this->item_pending = $this->total_item - $this->item_cancel - $this->item_purchas;
        $this->save();
    }

    public function updateStatus()
    {
        $pendingItems = $this->total_item - $this->item_purchas - $this->item_cancel - $this->item_partial;

        if ($pendingItems == 0 && $this->item_partial == 0 && $this->item_cancel != $this->total_item) {
            $this->status = 'Closed';
        } elseif ($this->item_cancel == $this->total_item) {
            $this->status = 'Void';
        } elseif ($this->item_partial != 0 && $pendingItems == 0) {
            $this->status = 'Partial';
        } else {
            $this->status = 'Pending';
        }

        $this->save();
    }

}