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
        'status',
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
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'pr_po_id', 'id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'pr_po_id', 'id');
    }

    // Generate unique cancellation_no
    public static function generateCancellationNo()
    {
        return DB::transaction(function () {
            $currentMonthYear = now()->format('Y-m');
            $prefix = "CANCEL-$currentMonthYear-";
    
            // Fetch the latest cancellation_no for the current month and year
            $latestCancellation = self::where('cancellation_no', 'LIKE', "$prefix%")
                ->lockForUpdate()
                ->orderBy('cancellation_no', 'desc')
                ->first();
    
            if ($latestCancellation) {
                // Extract the sequence number from the latest cancellation_no
                $lastSequence = (int) str_replace($prefix, '', $latestCancellation->cancellation_no);
                $newSequence = $lastSequence + 1;
            } else {
                // Start with sequence 1 if no cancellations exist for the current month and year
                $newSequence = 1;
            }
    
            // Generate the new cancellation_no
            $sequence = str_pad($newSequence, 4, '0', STR_PAD_LEFT);
            return "$prefix$sequence";
        });
    }
}
