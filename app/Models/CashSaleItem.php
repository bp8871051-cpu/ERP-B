<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function cashSale() { return $this->belongsTo(CashSale::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
