<?php

// app/Models/PurchaseRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}