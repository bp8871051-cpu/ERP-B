<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'cashier_id', 'customer_id', 'order_number',
        'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'payment_method', 'status'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(PosOrderItem::class); }
    public function transactions() { return $this->hasMany(PosTransaction::class); }
}
