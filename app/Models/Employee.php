<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'designation_id',
        'manager_id',
        'employee_code',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'email',
        'phone',
        'alternate_phone',
        'joining_date',
        'employment_type',
        'work_location',
        'salary',
        'pay_frequency',
        'status',
        'avatar',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'bank_name',
        'account_holder',
        'account_number',
        'ifsc',
        'pan',
        'aadhaar',
        'tax_info',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'date_of_birth' => 'date',
        'salary' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function salaryStructure()
    {
        return $this->hasOne(EmployeeSalaryStructure::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function trainings()
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function performanceGoals()
    {
        return $this->hasMany(PerformanceGoal::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
