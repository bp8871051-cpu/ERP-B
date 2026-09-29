<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'quantity',
        'unit_cost',
        'tax',
        'total',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchaseReturn() { return $this->belongsTo(PurchaseReturn::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
