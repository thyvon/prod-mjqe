<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'recommendation',
        'status',
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }
}
