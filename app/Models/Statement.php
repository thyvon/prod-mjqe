<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    use HasFactory;

    protected $table = 'clear_statements';

    protected $fillable = [
        'clear_by_id',
        'supplier_id',
        'clear_date',
        'total_amount',
        'total_invoices',
        'description',
        'remark',
        'status',
        'statement_number', // Add this to the fillable attributes
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($statement) {
            $statement->statement_number = self::generateStatementNumber();
        });
    }

    public static function generateStatementNumber()
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $count = 1;

        do {
            $statementNumber = sprintf('ST-%04d-%02d-%04d', $year, $month, $count);
            $count++;
        } while (self::where('statement_number', $statementNumber)->exists());

        return $statementNumber;
    }

    public function invoices()
    {
        return $this->hasMany(StatementIvoice::class, 'clear_statement_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'clear_by_id');
    }
}
