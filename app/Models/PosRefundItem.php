<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRefundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_refund_id',
        'pos_order_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'refund_amount',
        'restock_inventory',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'restock_inventory' => 'boolean',
    ];

    public function refund() { return $this->belongsTo(PosRefund::class, 'pos_refund_id'); }
    public function orderItem() { return $this->belongsTo(PosOrderItem::class, 'pos_order_item_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
