<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kh_name',
        'number',
        'email',
        'address',
        'payment_term',
        'currency',  // Ensure this is an integer
        'vat',
        'status',
    ];
    protected $casts = [
        'status' => 'integer',  // Make sure status is treated as an integer
    ];

    public static function search($query)
    {
        return self::where('name', 'like', '%' . $query . '%')->get();
    }

    public function Invoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier', 'name');
    }
}
