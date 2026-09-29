<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_quote_id',
        'product_id',
        'unit_id',
        'description',
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

    public function quote() { return $this->belongsTo(SalesQuote::class, 'sales_quote_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
