<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesQuote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'salesperson_id',
        'quote_number',
        'quote_date',
        'valid_until',
        'subtotal',
        'discount',
        'tax',
        'shipping',
        'grand_total',
        'status',
        'converted_order_id',
        'notes',
        'terms',
        'created_by',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesperson() { return $this->belongsTo(User::class, 'salesperson_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function convertedOrder() { return $this->belongsTo(SalesOrder::class, 'converted_order_id'); }
    public function items() { return $this->hasMany(SalesQuoteItem::class); }
}
