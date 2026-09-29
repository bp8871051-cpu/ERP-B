<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'warehouse_id',
        'salesperson_id',
        'order_number',
        'reference_number',
        'order_date',
        'expected_delivery_date',
        'delivery_date',
        'subtotal',
        'tax',
        'discount',
        'shipping',
        'total',
        'grand_total',
        'paid_amount',
        'due_amount',
        'payment_status',
        'status',
        'notes',
        'terms',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function salesperson() { return $this->belongsTo(User::class, 'salesperson_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(SalesOrderItem::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function deliveryNotes() { return $this->hasMany(DeliveryNote::class); }
}
