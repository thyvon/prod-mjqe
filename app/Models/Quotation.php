<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

        protected $fillable = [
        'evaluation_id', 'supplier_id', 'criteria', 'vat'
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'quotation_product')
                    ->withPivot('specification', 'quantity', 'price', 'discount', 'vat')
                    ->withTimestamps();
    }
}
