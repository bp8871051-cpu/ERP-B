<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['pos_order_id', 'amount', 'payment_type', 'reference_no'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
}
