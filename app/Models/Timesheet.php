<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'task_id', 'user_id', 'hours', 'date', 'description'];

    protected $casts = [
        'hours' => 'decimal:2',
        'date' => 'date',
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function task() { return $this->belongsTo(Task::class); }
    public function user() { return $this->belongsTo(User::class); }
}
