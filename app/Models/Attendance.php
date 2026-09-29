<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'working_hours',
        'working_minutes',
        'late_minutes',
        'overtime_minutes',
        'status',
        'notes',
        'remarks',
        'approved_by',
    ];

    protected $casts = [
        'date' => 'date',
        'working_hours' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
