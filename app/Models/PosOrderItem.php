<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['pos_order_id', 'product_id', 'quantity', 'unit_price', 'total_price'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
