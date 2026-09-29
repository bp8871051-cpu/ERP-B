<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'reference_type',
        'reference_id',
        'type', // backward compatible: in, out, transfer, adjustment
        'movement_type', // in, out, transfer_in, transfer_out, adjustment, opening, damage, sale, purchase, manual
        'quantity',
        'quantity_in',
        'quantity_out',
        'balance_quantity',
        'unit_cost',
        'total_cost',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_in' => 'integer',
        'quantity_out' => 'integer',
        'balance_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
