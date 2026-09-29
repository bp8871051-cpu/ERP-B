<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'workflow_id',
        'requester_id',
        'reference_number',
        'title',
        'module',
        'amount',
        'data',
        'current_step_id',
        'status', // pending, approved, rejected, changes_requested, cancelled
        'due_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'data' => 'array',
        'due_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function currentStep()
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function approvals()
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function comments()
    {
        return $this->hasMany(WorkflowComment::class)->latest();
    }

    public function logs()
    {
        return $this->hasMany(WorkflowLog::class)->latest();
    }
}
