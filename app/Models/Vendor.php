<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'vendor_code',
        'name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'alternate_phone',
        'website',
        'tax_number',
        'gst_number',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'bank_name',
        'account_holder',
        'account_number',
        'ifsc',
        'credit_limit',
        'payment_terms',
        'opening_balance',
        'balance',
        'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
    public function purchases() { return $this->hasMany(Purchase::class); }
    public function returns() { return $this->hasMany(PurchaseReturn::class); }
    public function payments() { return $this->hasMany(VendorPayment::class); }
}
