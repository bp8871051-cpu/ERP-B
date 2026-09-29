<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'delivery_number',
        'delivery_date',
        'delivery_address',
        'driver_name',
        'vehicle_number',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(DeliveryNoteItem::class); }
}
