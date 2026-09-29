<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'days_per_year',
        'is_paid',
        'carry_forward',
        'status',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'carry_forward' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
