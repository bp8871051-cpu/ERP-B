<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipTransaction extends Model
{
    use HasFactory;

    protected $table = 'membership_transactions';

    protected $fillable = [
        'company_id',
        'customer_id',
        'membership_id',
        'transaction_number',
        'amount',
        'tax',
        'discount',
        'total',
        'payment_method',
        'status',
        'payment_reference',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function membership() { return $this->belongsTo(Membership::class); }
}
