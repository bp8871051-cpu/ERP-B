<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'basic_salary',
        'hra',
        'transport_allowance',
        'medical_allowance',
        'special_allowance',
        'other_allowances',
        'pf',
        'esi',
        'professional_tax',
        'tds',
        'other_deductions',
        'effective_date',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'hra' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'special_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'pf' => 'decimal:2',
        'esi' => 'decimal:2',
        'professional_tax' => 'decimal:2',
        'tds' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getTotalAllowancesAttribute(): float
    {
        return (float) ($this->hra + $this->transport_allowance + $this->medical_allowance + $this->special_allowance + $this->other_allowances);
    }

    public function getTotalDeductionsAttribute(): float
    {
        return (float) ($this->pf + $this->esi + $this->professional_tax + $this->tds + $this->other_deductions);
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float) ($this->basic_salary + $this->total_allowances);
    }

    public function getNetSalaryAttribute(): float
    {
        return (float) ($this->gross_salary - $this->total_deductions);
    }
}
