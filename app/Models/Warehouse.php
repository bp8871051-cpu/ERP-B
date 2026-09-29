<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'location',
        'manager_name',
        'manager_id',
        'email',
        'phone',
        'type',
        'capacity',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'status',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function manager() { return $this->belongsTo(User::class, 'manager_id'); }
    public function stocks() { return $this->hasMany(Stock::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    public function stockAdjustments() { return $this->hasMany(StockAdjustment::class); }
    public function transfersFrom() { return $this->hasMany(StockTransfer::class, 'from_warehouse_id'); }
    public function transfersTo() { return $this->hasMany(StockTransfer::class, 'to_warehouse_id'); }

    public function getTotalProductsAttribute()
    {
        return $this->stocks()->where('quantity', '>', 0)->count();
    }

    public function getTotalStockValueAttribute()
    {
        return (float) $this->stocks()
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(stocks.quantity * COALESCE(products.purchase_price, products.cost_price, 0)) as val')
            ->value('val') ?? 0;
    }
}
