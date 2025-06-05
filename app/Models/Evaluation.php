<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'recommendation',
        'status',
        'created_by',
        'crated_at',
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }
}
