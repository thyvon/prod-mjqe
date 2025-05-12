<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentsItems extends Model
{
    use HasFactory;
    protected $table = 'documents_article';
    protected $fillable = [
        'documentation_id',
        'article_name',
        'description',
        'file_path',
        'file_name',
        'status',
    ];

    public function documentation()
    {
        return $this->belongsTo(Documents::class, 'documentation_id');
    }
}
