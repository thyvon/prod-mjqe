<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'approval';

    // Define the fillable attributes
    protected $fillable = [
        'approval_id',
        'docs_type',
        'status_type',
        'status',
        'user_id',
        'approval_name',
        'click_date',
    ];

    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashRequest()
    {
        return $this->belongsTo(CashRequest::class, 'approval_id');
    }
}
