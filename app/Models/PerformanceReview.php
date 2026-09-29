<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cycle_id',
        'employee_id',
        'reviewer_id',
        'self_rating',
        'self_comments',
        'manager_rating',
        'manager_comments',
        'final_rating',
        'final_score',
        'status',
    ];

    protected $casts = [
        'self_rating' => 'integer',
        'manager_rating' => 'integer',
        'final_rating' => 'integer',
        'final_score' => 'decimal:2',
    ];

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class, 'cycle_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
