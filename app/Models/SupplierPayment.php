<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_order_id', 'supplier_id', 'amount', 'payment_date', 'payment_method', 'reference'];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
