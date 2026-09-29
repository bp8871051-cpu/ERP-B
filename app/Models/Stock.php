<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'available_quantity',
        'reserved_quantity',
        'damaged_quantity',
        'average_cost',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'available_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'average_cost' => 'decimal:2',
        'last_movement_at' => 'datetime',
    ];

    protected $appends = [
        'stock_value',
        'status',
    ];

    protected static function booted()
    {
        static::saving(function ($stock) {
            $stock->quantity = (int) ($stock->available_quantity + $stock->reserved_quantity + $stock->damaged_quantity);
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }

    public function getStockValueAttribute(): float
    {
        $cost = $this->average_cost > 0 
            ? (float) $this->average_cost 
            : (float) ($this->product?->purchase_price ?? $this->product?->cost_price ?? 0);
        return round($this->quantity * $cost, 2);
    }

    public function getStatusAttribute(): string
    {
        $avail = $this->available_quantity;
        $alert = $this->product?->alert_quantity ?? $this->product?->minimum_stock ?? 10;
        $reorder = $this->product?->reorder_level ?? 20;

        if ($avail <= 0) {
            return 'out_of_stock';
        }
        if ($avail <= $alert) {
            return 'critical';
        }
        if ($avail <= $reorder) {
            return 'low_stock';
        }
        return 'healthy';
    }
}
