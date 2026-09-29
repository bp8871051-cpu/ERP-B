<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'sales_order_id',
        'warehouse_id',
        'invoice_number',
        'invoice_date',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'tax',
        'shipping',
        'round_off',
        'total',
        'grand_total',
        'amount_paid',
        'paid_amount',
        'due_amount',
        'payment_status',
        'status',
        'notes',
        'terms',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'round_off' => 'decimal:2',
        'total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(CustomerPayment::class); }
    public function creditNotes() { return $this->hasMany(CreditNote::class); }
    public function refunds() { return $this->hasMany(Refund::class); }
}
