<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cancellation extends Model
{
    use HasFactory;

    // Ensure all fields are fillable
    protected $fillable = [
        'cancellation_no', // Add cancellation_no here
        'cancellation_date',
        'cancellation_docs',
        'cancellation_reason',
        'pr_po_id', // Add this to allow mass assignment
        'cancellation_by',
    ];

    // Define relationships
    public function items()
    {
        return $this->hasMany(CancellationItems::class, 'cancellation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'cancellation_by');
    }

    // Generate unique cancellation_no
    public static function generateCancellationNo()
    {
        return DB::transaction(function () {
            $currentMonthYear = now()->format('m-Y');
            $count = self::where('cancellation_no', 'LIKE', "CANCEL-$currentMonthYear-%")
                ->lockForUpdate()
                ->count();

            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            return "CANCEL-$currentMonthYear-$sequence";
        });
    }
}
