<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosPaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'pos_order_id',
        'gateway',
        'transaction_id',
        'amount',
        'status',
        'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
}
