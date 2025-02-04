<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRequest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cash_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_type',
        'ref_no',
        'request_date',
        'user_id',
        'request_by',
        'position',
        'id_card',
        'campus',
        'division',
        'department',
        'description',
        'currency',
        'exchange_rate',
        'amount',
        'via',
        'reason',
        'remark',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'request_date' => 'datetime',
        'exchange_rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Define the relationship to the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Automatically generate the ref_no before creating the record.
     */
    public static function generateRefNo($requestType)
    {
        // Get today's date in yyyy-mm-dd format
        $date = now()->format('Ymd');

        // Find the latest record for the same type and day
        $latestRequest = self::where('request_type', $requestType)
            ->whereDate('request_date', today())
            ->latest('created_at')
            ->first();

        // Get the latest sequential number or start from 001
        $sequentialNumber = $latestRequest ? (intval(substr($latestRequest->ref_no, -3)) + 1) : 1;
        $sequentialNumber = str_pad($sequentialNumber, 3, '0', STR_PAD_LEFT);

        $campus = auth()->user()->campus; // Assuming the campus is stored in the user model

        $refNo = '';
        if ($requestType == 1) {
            // Format for request_type 1: P-campus-yyyy-mm-dd-count(001)
            $refNo = "P-{$campus}-{$date}-{$sequentialNumber}";
        } elseif ($requestType == 2) {
            // Format for request_type 2: A-campus-yyyy-mm-dd-count(001)
            $refNo = "A-{$campus}-{$date}-{$sequentialNumber}";
        } else {
            // Default format: requestType-yyyy-mm-dd-count(001)
            $refNo = "{$requestType}-{$date}-{$sequentialNumber}";
        }

        // Ensure the ref_no is unique
        while (self::where('ref_no', $refNo)->exists()) {
            $sequentialNumber = str_pad(intval($sequentialNumber) + 1, 3, '0', STR_PAD_LEFT);
            if ($requestType == 1) {
                $refNo = "P-{$campus}-{$date}-{$sequentialNumber}";
            } elseif ($requestType == 2) {
                $refNo = "A-{$campus}-{$date}-{$sequentialNumber}";
            } else {
                $refNo = "{$requestType}-{$date}-{$sequentialNumber}";
            }
        }

        return $refNo;
    }
}
