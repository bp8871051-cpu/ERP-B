<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashflowTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'account_id',
        'date',
        'reference',
        'type',
        'category',
        'amount',
        'balance_after',
        'description',
        'sourceable_type',
        'sourceable_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function sourceable()
    {
        return $this->morphTo();
    }
}
