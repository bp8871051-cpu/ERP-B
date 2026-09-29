<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function transfer() { return $this->belongsTo(StockTransfer::class, 'transfer_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
