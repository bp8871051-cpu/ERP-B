<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_code',
        'name',
        'company_name',
        'email',
        'phone',
        'alternate_phone',
        'website',
        'tax_number',
        'gst_number',
        'billing_address',
        'shipping_address',
        'city',
        'state',
        'country',
        'postal_code',
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
    public function contacts() { return $this->hasMany(Contact::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function salesOrders() { return $this->hasMany(SalesOrder::class); }
    public function quotes() { return $this->hasMany(SalesQuote::class); }
    public function creditNotes() { return $this->hasMany(CreditNote::class); }
    public function refunds() { return $this->hasMany(Refund::class); }
    public function deliveryNotes() { return $this->hasMany(DeliveryNote::class); }
    public function payments() { return $this->hasMany(CustomerPayment::class); }
    public function opportunities() { return $this->hasMany(Opportunity::class); }
    public function tickets() { return $this->hasMany(Ticket::class); }
    public function crmContacts() { return $this->hasMany(CrmContact::class, 'customer_id'); }
    public function crmDeals() { return $this->hasMany(CrmDeal::class, 'customer_id'); }
    public function crmActivities() { return $this->hasMany(CrmActivity::class, 'customer_id'); }
    public function crmFeedback() { return $this->hasMany(CrmFeedback::class, 'customer_id'); }
    public function crmTasks() { return $this->hasMany(CrmTask::class, 'customer_id'); }
    public function crmSegments() { return $this->belongsToMany(CrmCustomerSegment::class, 'crm_segment_members', 'customer_id', 'segment_id'); }
}
