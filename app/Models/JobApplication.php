<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'candidate_id',
        'job_position_id',
        'applied_date',
        'stage',
        'rating',
        'notes',
        'status',
    ];

    protected $casts = [
        'applied_date' => 'date',
        'rating' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'candidate_id', 'candidate_id');
    }
}
