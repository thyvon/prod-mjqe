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
        'status',
    ];
    protected $casts = [
        'status' => 'integer',  // Make sure status is treated as an integer
    ];
}