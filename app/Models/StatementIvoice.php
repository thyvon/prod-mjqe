<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatementIvoice extends Model
{
    use HasFactory;

    protected $table = 'clear_statement_invoices';

    protected $fillable = [
        'clear_statement_id',
        'invoice_id',
        'supplier_id',
        'clear_by_id',
        'clear_date',
        'total_amount',
        'status',
    ];

    public function clearStatement()
    {
        return $this->belongsTo(Statement::class, 'clear_statement_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'clear_by_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }
}
