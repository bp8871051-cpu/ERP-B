<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'inspection_number',
        'purchase_order_id',
        'purchase_id',
        'product_id',
        'warehouse_id',
        'inspector_id',
        'lot_number',
        'inspection_type',
        'batch_quantity',
        'sample_size',
        'status',
        'decision_date',
        'defect_count',
        'measurements',
        'checklist',
        'notes',
        'reinspection_date',
    ];

    protected $casts = [
        'decision_date' => 'datetime',
        'reinspection_date' => 'date',
        'measurements' => 'array',
        'checklist' => 'array',
        'batch_quantity' => 'integer',
        'sample_size' => 'integer',
        'defect_count' => 'integer',
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

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function ncrs()
    {
        return $this->hasMany(QcNcr::class, 'inspection_id');
    }
}
