<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_order_id',
        'product_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'subtotal',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
