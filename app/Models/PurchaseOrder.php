<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'supplier_id',
        'warehouse_id',
        'created_by',
        'po_number',
        'po_date',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'discount',
        'tax',
        'shipping',
        'total',
        'grand_total',
        'status',
        'payment_status',
        'notes',
        'terms',
    ];

    protected $casts = [
        'po_date' => 'date',
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
    public function purchases() { return $this->hasMany(Purchase::class); }
    public function payments() { return $this->hasMany(SupplierPayment::class); }
}
