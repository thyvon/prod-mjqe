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
    protected $table = 'cash_request';

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
        'exchange_rate' => 'decimal:4',
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
        // Get today's date in ddmmyy format
        $date = now()->format('dmy');

        // Find the latest record for the same type and day
        $latestRequest = self::where('request_type', $requestType)
            ->whereDate('request_date', today()) // Filter by today's date
            ->latest('created_at')
            ->first();

        // Get the latest sequential number or start from 1001
        $sequentialNumber = $latestRequest ? (intval(substr($latestRequest->ref_no, -4)) + 1) : 1001;

        // Return the ref_no in the desired format
        return "{$requestType}-{$date}-{$sequentialNumber}";
    }
}
