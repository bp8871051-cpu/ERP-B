<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'current_stock',
        'average_daily_sales',
        'days_remaining',
        'predicted_stockout_date',
        'recommended_order_quantity',
        'supplier_id',
        'estimated_cost',
        'confidence_score',
    ];

    protected $casts = [
        'average_daily_sales' => 'decimal:2',
        'days_remaining' => 'decimal:1',
        'predicted_stockout_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'confidence_score' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
