<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportSlaPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'support_sla_policies';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'priority',
        'first_response_target_minutes',
        'resolution_target_minutes',
        'business_hours',
        'department_id',
        'customer_type',
        'status',
    ];

    protected $casts = [
        'business_hours' => 'boolean',
        'first_response_target_minutes' => 'integer',
        'resolution_target_minutes' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function tickets() { return $this->hasMany(Ticket::class, 'sla_policy_id'); }
}
