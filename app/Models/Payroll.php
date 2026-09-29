<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'payroll_period_id',
        'month',
        'year',
        'basic_salary',
        'allowances',
        'gross_salary',
        'deductions',
        'overtime_amount',
        'net_salary',
        'working_days',
        'present_days',
        'absent_days',
        'paid_leaves',
        'unpaid_leaves',
        'overtime_hours',
        'status',
        'payment_date',
        'processed_at',
        'paid_at',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'deductions' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'payment_date' => 'date',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($payroll) {
            if (empty($payroll->month)) {
                $payroll->month = now()->format('F');
            }
            if (empty($payroll->year)) {
                $payroll->year = now()->year;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function payslip()
    {
        return $this->hasOne(Payslip::class);
    }
}
