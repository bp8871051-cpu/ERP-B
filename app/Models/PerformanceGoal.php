<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cycle_id',
        'employee_id',
        'title',
        'description',
        'kpi',
        'weight',
        'target_value',
        'achieved_value',
        'status',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class, 'cycle_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
