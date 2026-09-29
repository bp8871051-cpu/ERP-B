<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_order',
        'name',
        'approver_role',
        'approver_user_id',
        'type', // sequential, parallel
        'sla_hours',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function approverUser()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
