<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'purchase_id',
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
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
