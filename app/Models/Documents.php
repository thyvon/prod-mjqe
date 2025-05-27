<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documents extends Model
{
    use HasFactory;

    protected $table = 'documentations';
    protected $fillable = [
        'section_name',
        'description',
        'icon_class',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(DocumentsItems::class, 'documentation_id');
    }
}
