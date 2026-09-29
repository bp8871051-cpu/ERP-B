<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'payment_number',
        'payment_date',
        'payment_type',
        'party_type',
        'party_id',
        'party_name',
        'invoice_id',
        'purchase_id',
        'expense_id',
        'account_id',
        'payment_method',
        'reference',
        'amount',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'party_id');
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'reference_id')
            ->where('reference_type', 'Payment');
    }
}
