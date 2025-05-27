<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    use HasFactory;

    // Specify which fields are mass-assignable
    protected $fillable = ['name', 'remark'];

    // Optionally cast 'remark' to string (if needed)
    protected $casts = [
        'remark' => 'string',
    ];

    // Define the relationship with products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
