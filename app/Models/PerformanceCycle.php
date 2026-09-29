<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
        'year',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function goals()
    {
        return $this->hasMany(PerformanceGoal::class, 'cycle_id');
    }

    public function reviews()
    {
        return $this->hasMany(PerformanceReview::class, 'cycle_id');
    }
}
