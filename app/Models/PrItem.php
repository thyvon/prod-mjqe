<?php

// app/Models/PrItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_id',
        'product_id',
        'remark',
        'qty',
        'uom',
        'price',
        'total_price',
        'campus',
        'division',
        'department',
    ];

    /**
     * Get the purchase request that owns the PR item.
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class); // Ensure the relationship is correctly defined
    }

    /**
     * Get the product that is associated with the PR item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class); // Ensure the relationship is correctly defined
    }
}
