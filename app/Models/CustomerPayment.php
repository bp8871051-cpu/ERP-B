<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'payment_number',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
