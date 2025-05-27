<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Telegram extends Model
{
        use HasFactory;
        protected $fillable = [
        'chat_id',
        'direction',
        'message',
        'file_url',
        'type',
        'name',
        'photo_url',
    ];
}
