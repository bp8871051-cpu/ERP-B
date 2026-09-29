<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'allocated',
        'used',
        'pending',
        'remaining',
    ];

    protected $casts = [
        'allocated' => 'decimal:1',
        'used' => 'decimal:1',
        'pending' => 'decimal:1',
        'remaining' => 'decimal:1',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
