<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'purchase_id',
        'warehouse_id',
        'return_number',
        'return_date',
        'reason',
        'subtotal',
        'tax',
        'grand_total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(PurchaseReturnItem::class); }
}
