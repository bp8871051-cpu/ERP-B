<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'designation_id',
        'job_code',
        'title',
        'location',
        'employment_type',
        'experience',
        'min_salary',
        'max_salary',
        'vacancies',
        'status',
        'requirements',
        'description',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
    ];

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

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }
}
