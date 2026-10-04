<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipRenewal extends Model
{
    use HasFactory;

    protected $table = 'membership_renewals';

    protected $fillable = [
        'company_id',
        'membership_id',
        'previous_plan_id',
        'new_plan_id',
        'previous_expiry',
        'new_expiry',
        'renewal_amount',
        'discount',
        'tax',
        'total_paid',
        'renewal_type',
        'payment_status',
        'renewed_by',
    ];

    protected $casts = [
        'previous_expiry' => 'date',
        'new_expiry' => 'date',
        'renewal_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function membership() { return $this->belongsTo(Membership::class); }
    public function previousPlan() { return $this->belongsTo(MembershipPlan::class, 'previous_plan_id'); }
    public function newPlan() { return $this->belongsTo(MembershipPlan::class, 'new_plan_id'); }
    public function renewedByUser() { return $this->belongsTo(User::class, 'renewed_by'); }
}
