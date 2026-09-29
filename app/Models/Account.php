<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_name',
        'bank_name',
        'account_number',
        'type',
        'account_type',
        'balance',
        'current_balance',
        'opening_balance',
        'currency',
        'is_default',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function getCurrentBalanceAttribute()
    {
        return $this->attributes['balance'] ?? 0;
    }

    public function setCurrentBalanceAttribute($val)
    {
        $this->attributes['balance'] = $val;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cashflowTransactions()
    {
        return $this->hasMany(CashflowTransaction::class);
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
