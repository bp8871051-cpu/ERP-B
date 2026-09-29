<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'snapshot_date',
        'total_products',
        'total_units',
        'total_stock_value',
        'total_purchase_value',
        'total_sales_value',
        'low_stock_count',
        'out_of_stock_count',
        'warehouse_utilization',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'total_stock_value' => 'decimal:2',
        'total_purchase_value' => 'decimal:2',
        'total_sales_value' => 'decimal:2',
        'warehouse_utilization' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
