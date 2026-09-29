<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_request_id',
        'workflow_step_id',
        'approver_id',
        'action', // pending, approved, rejected, changes_requested
        'comments',
        'action_taken_at',
    ];

    protected $casts = [
        'action_taken_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(WorkflowRequest::class, 'workflow_request_id');
    }

    public function step()
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
