<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_id',
        'product_id',
        'system_quantity',
        'actual_quantity',
        'difference',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function adjustment() { return $this->belongsTo(StockAdjustment::class, 'adjustment_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
