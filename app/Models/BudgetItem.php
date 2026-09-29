<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'category_id',
        'item_name',
        'planned_amount',
        'actual_amount',
        'notes',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
