<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status', // draft, pending, approved, in_transit, received, cancelled
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'received_by',
        'received_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function fromWarehouse() { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse() { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
    public function items() { return $this->hasMany(StockTransferItem::class, 'transfer_id'); }
}
