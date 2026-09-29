<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'job_position_id',
        'name',
        'email',
        'phone',
        'experience',
        'skills',
        'location',
        'source',
        'resume_path',
        'notes',
        'stage',
        'expected_salary',
        'applied_date',
    ];

    protected $casts = [
        'applied_date' => 'date',
        'expected_salary' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function offers()
    {
        return $this->hasMany(JobOffer::class);
    }
}
