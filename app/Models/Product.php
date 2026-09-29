<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'brand_id',
        'unit_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'product_code',
        'cost_price',
        'purchase_price',
        'selling_price',
        'mrp',
        'discount',
        'tax_rate',
        'tax_type',
        'alert_quantity',
        'minimum_stock',
        'maximum_stock',
        'reorder_level',
        'default_warehouse_id',
        'image',
        'description',
        'track_inventory',
        'track_batch',
        'track_serial',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'track_inventory' => 'boolean',
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
    ];

    protected $appends = [
        'total_stock',
        'available_stock',
        'stock_status',
    ];

    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->slug) && !empty($product->name)) {
                $product->slug = Str::slug($product->name) . '-' . strtolower(Str::random(5));
            }
            if (empty($product->purchase_price) && !empty($product->cost_price)) {
                $product->purchase_price = $product->cost_price;
            } elseif (empty($product->cost_price) && !empty($product->purchase_price)) {
                $product->cost_price = $product->purchase_price;
            }
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function defaultWarehouse() { return $this->belongsTo(Warehouse::class, 'default_warehouse_id'); }
    public function stocks() { return $this->hasMany(Stock::class); }
    public function movements() { return $this->hasMany(StockMovement::class); }
    public function adjustmentItems() { return $this->hasMany(StockAdjustmentItem::class); }
    public function transferItems() { return $this->hasMany(StockTransferItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    public function getTotalStockAttribute()
    {
        if ($this->relationLoaded('stocks')) {
            return (int) $this->stocks->sum('quantity');
        }
        return (int) $this->stocks()->sum('quantity');
    }

    public function getAvailableStockAttribute()
    {
        if ($this->relationLoaded('stocks')) {
            return (int) $this->stocks->sum('available_quantity');
        }
        return (int) $this->stocks()->sum('available_quantity');
    }

    public function getReservedStockAttribute()
    {
        if ($this->relationLoaded('stocks')) {
            return (int) $this->stocks->sum('reserved_quantity');
        }
        return (int) $this->stocks()->sum('reserved_quantity');
    }

    public function getDamagedStockAttribute()
    {
        if ($this->relationLoaded('stocks')) {
            return (int) $this->stocks->sum('damaged_quantity');
        }
        return (int) $this->stocks()->sum('damaged_quantity');
    }

    public function getStockStatusAttribute(): string
    {
        $stock = $this->total_stock;
        $alert = $this->alert_quantity ?? $this->minimum_stock ?? 10;
        $reorder = $this->reorder_level ?? 20;

        if ($stock <= 0) {
            return 'out_of_stock';
        }
        if ($stock <= $alert) {
            return 'critical';
        }
        if ($stock <= $reorder) {
            return 'low_stock';
        }
        return 'healthy';
    }
}
