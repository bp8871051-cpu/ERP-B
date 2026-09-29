<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'financial_year',
        'start_date',
        'end_date',
        'department_id',
        'category_id',
        'budget_amount',
        'actual_spent',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_amount' => 'decimal:2',
        'actual_spent' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function items()
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRemainingAttribute(): float
    {
        return (float) ($this->budget_amount - $this->actual_spent);
    }

    public function getUtilizationAttribute(): float
    {
        if ($this->budget_amount <= 0) return 0.0;
        return round(($this->actual_spent / $this->budget_amount) * 100, 1);
    }
}
