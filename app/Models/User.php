<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'card_id',
        'position',
        'campus',
        'division',
        'department',
        'phone',
        'extension',
        'signature', // Added
        'profile',   // Added
        'microsoft_id',
        'microsoft_token',
        'microsoft_refresh_token',
        'microsoft_token_expires', // Added
        'telegram_id', // Added
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'microsoft_token',
        'microsoft_refresh_token',
        'microsoft_token_expires', // Added
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function approvals()
    {
        return $this->hasMany(Approval::class, 'user_id');
    }
}
