<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'workflow_step_id',
        'approver_id',
        'status',
        'comments',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function workflowStep() { return $this->belongsTo(DocumentWorkflowStep::class, 'workflow_step_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
}
