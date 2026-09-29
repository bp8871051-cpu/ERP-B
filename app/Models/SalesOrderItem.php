<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'unit_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(SalesOrder::class, 'sales_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
