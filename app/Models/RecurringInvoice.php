<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'template_id',
        'start_date',
        'end_date',
        'frequency',
        'amount',
        'next_invoice_date',
        'payment_terms',
        'status',
        'items_json',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_invoice_date' => 'date',
        'amount' => 'decimal:2',
        'items_json' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function template() { return $this->belongsTo(InvoiceTemplate::class, 'template_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
