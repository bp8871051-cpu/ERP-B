<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_name',
        'sequence',
        'approver_type',
        'approver_id',
        'approver_role',
        'approver_department_id',
        'is_required',
        'sla_hours',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_required' => 'boolean',
        'sla_hours' => 'integer',
    ];

    public function workflow() { return $this->belongsTo(DocumentWorkflow::class, 'workflow_id'); }
    public function approverUser() { return $this->belongsTo(User::class, 'approver_id'); }
    public function approverDepartment() { return $this->belongsTo(Department::class, 'approver_department_id'); }
}
