<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'title',
        'session_date',
        'start_time',
        'end_time',
        'room',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'program_id');
    }
}
