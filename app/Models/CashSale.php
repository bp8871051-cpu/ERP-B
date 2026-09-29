<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'warehouse_id',
        'sale_number',
        'sale_date',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'invoice_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(CashSaleItem::class); }
}
