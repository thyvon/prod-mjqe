<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Specify the fields that can be mass assigned
    protected $fillable = [
        'sku',
        'product_description',
        'brand',
        'category_id',
        'group_id',
        'price',
        'uom',
        'quantity',
        'status',
    ];

    // Set up the "creating" event to auto-generate the SKU
    protected static function booted()
    {
        static::creating(function ($product) {
            if (!$product->sku) {
                // Get the last product's SKU to increment it
                $lastProduct = Product::latest('id')->first();
                $lastSku = $lastProduct ? (int) substr($lastProduct->sku, 5) : 0;
                $newSku = 'PROD-' . str_pad($lastSku + 1, 6, '0', STR_PAD_LEFT);
                $product->sku = $newSku;
            }
        });
    }

    // Cast fields to appropriate data types
    protected $casts = [
        'status' => 'integer',  // status field treated as an integer
        'category_id' => 'integer',  // Ensure category_id is an integer
        'group_id' => 'integer',     // Ensure group_id is an integer
    ];

    // Define the relationships
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function group()
    {
        return $this->belongsTo(ProductGroup::class);
    }
}
