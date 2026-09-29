<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'refund_number',
        'refund_date',
        'reason',
        'refund_amount',
        'refund_method',
        'restock_inventory',
        'status',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'refund_date' => 'date',
        'refund_amount' => 'decimal:2',
        'restock_inventory' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
