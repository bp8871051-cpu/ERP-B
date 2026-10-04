<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'pos_order_id',
        'payment_method',
        'amount',
        'currency',
        'reference_no',
        'card_last4',
        'card_type',
        'upi_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
}
