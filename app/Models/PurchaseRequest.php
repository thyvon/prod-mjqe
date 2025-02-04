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
}
