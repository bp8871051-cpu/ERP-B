<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'membership_plans';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'price',
        'billing_cycle',
        'trial_period_days',
        'setup_fee',
        'discount',
        'tax_rate',
        'max_users',
        'storage_limit_gb',
        'features',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'max_users' => 'integer',
        'storage_limit_gb' => 'integer',
        'trial_period_days' => 'integer',
        'features' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function memberships() { return $this->hasMany(Membership::class, 'plan_id'); }
}
