<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'candidate_id',
        'job_position_id',
        'interview_type',
        'interview_date',
        'interview_time',
        'interviewers',
        'meeting_link',
        'feedback',
        'rating',
        'status',
    ];

    protected $casts = [
        'interview_date' => 'date',
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
}
