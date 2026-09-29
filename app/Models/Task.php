<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'project_id',
        'assigned_to',
        'reporter_id',
        'title',
        'description',
        'priority',
        'status',
        'category',
        'order',
        'start_date',
        'due_date',
        'reminder_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'reminder_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees', 'task_id', 'user_id')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function checklists()
    {
        return $this->hasMany(TaskChecklist::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function labels()
    {
        return $this->hasMany(TaskLabel::class);
    }
}
