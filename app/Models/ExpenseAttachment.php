<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
