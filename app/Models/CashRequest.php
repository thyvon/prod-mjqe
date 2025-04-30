<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRequest extends Model
{
    use HasFactory;

    protected $table = 'cash_requests';

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
        'amount_usd', // Add this field
        'via',
        'reason',
        'remark',
        'status',
        'approval_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clearInvoice()
    {
        return $this->hasOne(ClearInvoice::class);
    }

    public function purchaseInvoice()
    {
        return $this->hasMany(PurchaseInvoice::class, 'cash_ref');
    }

    public static function generateRefNo($requestType)
    {
        $date = now()->format('Ymd');
        $latestRequest = self::where('request_type', $requestType)
            ->whereDate('request_date', today())
            ->latest('created_at')
            ->first();

        $sequentialNumber = $latestRequest ? (intval(substr($latestRequest->ref_no, -3)) + 1) : 1;
        $sequentialNumber = str_pad($sequentialNumber, 3, '0', STR_PAD_LEFT);

        $campus = request()->input('campus');

        if ($requestType == 1) {
            $refNo = "P-{$campus}-{$date}-{$sequentialNumber}";
        } elseif ($requestType == 2) {
            $refNo = "A-{$campus}-{$date}-{$sequentialNumber}";
        } else {
            $refNo = "{$requestType}-{$date}-{$sequentialNumber}";
        }

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

    public function approve()
    {
        $this->status = 1;
        $this->save();
    }
}
