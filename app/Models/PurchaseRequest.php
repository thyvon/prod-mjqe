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
    ];

    public function prItems()
    {
        return $this->hasMany(PrItem::class); // Changed line
    }

    public function requestBy()
    {
        return $this->belongsTo(User::class, 'request_by');
    }
}
