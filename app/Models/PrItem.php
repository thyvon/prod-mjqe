<?php

// app/Models/PrItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'qty_last',
        'qty_po',
        'qty_purchase',
        'status',
        'force_close',
        'is_cancel',
        'reason',
    ];

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
        return $this->hasMany(PoItem::class, 'pr_item_id');
    }
}
