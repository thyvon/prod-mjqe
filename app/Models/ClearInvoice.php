<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearInvoice extends Model
{
    use HasFactory;
    protected $table = 'clear_invoices';

    protected $fillable = [
        'ref_no', // Ensure 'ref_no' is fillable
        'description',
        'remark',
        'clear_type',
        'clear_by',
        'status',
        'clear_date',
        'cash_id',
    ];

    public function cashRequest()
    {
        return $this->belongsTo(CashRequest::class, 'cash_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'clear_by');
    }

    public static function generateRefNo()
    {
        $date = now()->format('Ym');
        $latestInvoice = self::whereMonth('clear_date', now()->month)
            ->latest('created_at')
            ->first();

        $sequentialNumber = $latestInvoice ? (intval(substr($latestInvoice->ref_no, -4)) + 1) : 1;
        $sequentialNumber = str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);

        $refNo = "CI-{$date}-{$sequentialNumber}";

        while (self::where('ref_no', $refNo)->exists()) {
            $sequentialNumber = str_pad(intval($sequentialNumber) + 1, 4, '0', STR_PAD_LEFT);
            $refNo = "CI-{$date}-{$sequentialNumber}";
        }

        return $refNo;
    }
}


