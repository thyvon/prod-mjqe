<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseInvoiceItem;

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
        'avg_price',
        'uom',
        'quantity',
        'status',
        'image_path',
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

    public function updatePriceFromLatestPurchase()
    {
        $latestPurchase = PurchaseInvoiceItem::where('item_code', $this->id)
        ->where('payment_type', 1)
        ->orderBy('invoice_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();
    
        if ($latestPurchase) {
            $unitPrice = $latestPurchase->unit_price;
    
            // Check if the currency is 2 and convert the price
            if ($latestPurchase->currency == 2 && $latestPurchase->currency_rate > 0) {
                $unitPrice = $unitPrice / $latestPurchase->currency_rate;
            } else {
                $unitPrice = $latestPurchase->unit_price; // Keep the original unit price
            }
    
            $this->price = $unitPrice;
            $this->save();
        }
    }

    public function calculateAveragePriceLastThreeMonths()
    {
        $threeMonthsAgo = now()->subMonths(3);
    
        $purchases = PurchaseInvoiceItem::where('item_code', $this->id)
            ->where('payment_type', 1)
            ->where('invoice_date', '>=', $threeMonthsAgo)
            ->get();
    
        $totalPrice = 0;
        $totalCount = 0;
    
        foreach ($purchases as $purchase) {
            $unitPrice = $purchase->unit_price;
    
            // Convert price if currency is 2
            if ($purchase->currency == 2 && $purchase->currency_rate > 0) {
                $unitPrice = $unitPrice / $purchase->currency_rate;
            } 
            // No conversion needed if currency is 1
            else if ($purchase->currency == 1) {
                $unitPrice = $purchase->unit_price;
            }
    
            $totalPrice += $unitPrice;
            $totalCount++;
        }
    
        $averagePrice = $totalCount > 0 ? $totalPrice / $totalCount : 0;
    
        $this->avg_price = $averagePrice; // Save the calculated average price
        $this->save();
    
        return $this->avg_price;
    }

        public function quotations()
        {
            return $this->belongsToMany(Quotation::class, 'quotation_product')
                        ->withPivot('specification', 'quantity', 'price', 'discount', 'vat')
                        ->withTimestamps();
        }
}
