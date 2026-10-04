<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'register_session_id',
        'cashier_id',
        'customer_id',
        'invoice_id',
        'order_number',
        'subtotal',
        'discount_amount',
        'round_off',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'payment_status',
        'order_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'round_off' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function registerSession() { return $this->belongsTo(PosRegisterSession::class, 'register_session_id'); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function items() { return $this->hasMany(PosOrderItem::class); }
    public function payments() { return $this->hasMany(PosPayment::class); }
    public function transactions() { return $this->hasMany(PosTransaction::class); }
    public function refunds() { return $this->hasMany(PosRefund::class); }
    public function receipt() { return $this->hasOne(PosReceipt::class); }
}
