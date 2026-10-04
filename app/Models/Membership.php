<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'memberships';

    protected $fillable = [
        'company_id',
        'customer_id',
        'plan_id',
        'membership_number',
        'status',
        'start_date',
        'expiry_date',
        'trial_ends_at',
        'billing_cycle',
        'auto_renew',
        'total_amount',
        'payment_method',
        'notes',
        'paused_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expiry_date' => 'date',
        'trial_ends_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'total_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function plan() { return $this->belongsTo(MembershipPlan::class, 'plan_id'); }
    public function addonItems() { return $this->hasMany(MembershipAddonItem::class); }
    public function transactions() { return $this->hasMany(MembershipTransaction::class); }
    public function renewals() { return $this->hasMany(MembershipRenewal::class)->latest(); }
    public function usages() { return $this->hasMany(MembershipUsage::class); }
}
