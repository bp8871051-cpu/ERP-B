<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_code',
        'name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'alternate_phone',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_id',
        'gst_number',
        'bank_name',
        'account_number',
        'status',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
}
