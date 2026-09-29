<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTraining extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'program_id',
        'employee_id',
        'enrollment_date',
        'completion_status',
        'score',
        'certificate_path',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'score' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'program_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
