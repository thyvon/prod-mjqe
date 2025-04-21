<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $table = 'purchase_invoices'; // Corrected table name

    protected $fillable = [
        'pi_number',
        'transaction_type',
        'cash_ref',
        'payment_type',
        'invoice_date',
        'invoice_no',
        'supplier',
        'currency',
        'currency_rate',
        'payment_term',
        'sub_total',
        'vat_rate',
        'vat_amount',
        'discount_total',
        'service_charge',
        'total_amount',
        'paid_amount',
        'created_by',
        'paid_usd',
        'purchased_by',
        'status',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->pi_number = self::generateUniquePiNumber();
        });
    }

    public static function generateUniquePiNumber()
    {
        $date = Carbon::now();
        $year = $date->year;
        $month = $date->month;
        $count = self::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;
        $piNumber = sprintf('PI-%04d-%02d-%06d', $year, $month, $count);

        while (self::where('pi_number', $piNumber)->exists()) {
            $count++;
            $piNumber = sprintf('PI-%04d-%02d-%06d', $year, $month, $count);
        }

        return $piNumber;
    }

    public function cashRequest()
    {
        return $this->belongsTo(CashRequest::class, 'cash_ref');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier', 'id'); // Ensure the foreign key and local key are correct
    }

    public function SupplierName()
    {
        return $this->belongsTo(Supplier::class, 'supplier', 'id'); // Ensure the foreign key and local key are correct
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'pi_number');
    }

    public function attachments()
    {
        return $this->hasMany(InvoiceAttachment::class, 'purchase_invoice_id');
    }

    public function purchasedBy()
    {
        return $this->belongsTo(User::class, 'purchased_by', 'id'); // Ensure the foreign key and local key are correct
    }
}
