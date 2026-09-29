<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
        'trainer',
        'description',
        'start_date',
        'end_date',
        'duration_hours',
        'location',
        'cost',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_hours' => 'integer',
        'cost' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sessions()
    {
        return $this->hasMany(TrainingSession::class, 'program_id');
    }

    public function enrollments()
    {
        return $this->hasMany(EmployeeTraining::class, 'program_id');
    }
}
