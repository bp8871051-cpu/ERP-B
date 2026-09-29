<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmDealItem extends Model
{
    use HasFactory;

    protected $table = 'crm_deal_items';

    protected $fillable = [
        'deal_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function deal()
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
