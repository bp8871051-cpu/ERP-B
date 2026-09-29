<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'expense_number',
        'account_id',
        'expense_category_id',
        'vendor_id',
        'category',
        'amount',
        'tax',
        'discount',
        'total',
        'expense_date',
        'payment_method',
        'reference',
        'description',
        'attachment',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($expense) {
            if (empty($expense->category)) {
                $expense->category = $expense->categoryItem?->name ?? 'General Operating Expense';
            }
            if (empty($expense->total)) {
                $expense->total = ($expense->amount ?? 0) + ($expense->tax ?? 0) - ($expense->discount ?? 0);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function categoryItem()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments()
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'reference_id')
            ->where('reference_type', 'Expense');
    }
}
