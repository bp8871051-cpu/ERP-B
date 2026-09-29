<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'purchase_order_id',
        'warehouse_id',
        'purchase_number',
        'vendor_invoice_number',
        'invoice_date',
        'subtotal',
        'discount',
        'tax',
        'shipping',
        'grand_total',
        'paid_amount',
        'due_amount',
        'payment_status',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(PurchaseItem::class); }
    public function returns() { return $this->hasMany(PurchaseReturn::class); }
    public function payments() { return $this->hasMany(VendorPayment::class); }
}
