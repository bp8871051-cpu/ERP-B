<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'candidate_id',
        'job_position_id',
        'offer_date',
        'joining_date',
        'salary',
        'benefits',
        'status',
        'offer_letter_path',
    ];

    protected $casts = [
        'offer_date' => 'date',
        'joining_date' => 'date',
        'salary' => 'decimal:2',
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
