<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'account_id', 'source', 'amount', 'income_date', 'reference', 'description'];

    protected $casts = [
        'amount' => 'decimal:2',
        'income_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function account() { return $this->belongsTo(Account::class); }
}
