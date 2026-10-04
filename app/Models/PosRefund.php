<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'pos_order_id',
        'customer_id',
        'refund_number',
        'refund_amount',
        'refund_method',
        'reason',
        'processed_by',
        'status',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
    ];

    public function getAmountAttribute() { return $this->refund_amount; }
    public function setAmountAttribute($value) { $this->attributes['refund_amount'] = $value; }

    public function getPaymentMethodAttribute() { return $this->refund_method; }
    public function setPaymentMethodAttribute($value) { $this->attributes['refund_method'] = $value; }

    public function getCashierIdAttribute() { return $this->processed_by; }
    public function setCashierIdAttribute($value) { $this->attributes['processed_by'] = $value; }

    public function company() { return $this->belongsTo(Company::class); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function cashier() { return $this->belongsTo(User::class, 'processed_by'); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
    public function items() { return $this->hasMany(PosRefundItem::class); }
}
