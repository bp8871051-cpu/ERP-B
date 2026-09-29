<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'code',
        'description',
        'budget',
        'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'category_id');
    }
}
