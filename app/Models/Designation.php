<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'title',
        'code',
        'level',
        'description',
        'min_salary',
        'max_salary',
        'status',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function jobPositions()
    {
        return $this->hasMany(JobPosition::class);
    }
}
