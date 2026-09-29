<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'reference_number',
        'adjustment_type', // increase, decrease, both
        'reason',
        'status', // draft, pending, approved, rejected, applied
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id'); }
}
