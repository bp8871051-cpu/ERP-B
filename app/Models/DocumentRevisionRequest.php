<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRevisionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'document_id',
        'requested_by',
        'from_version',
        'target_version',
        'reason',
        'checklist_feedback',
        'status', // pending, resolved, rejected
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'checklist_feedback' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function document() { return $this->belongsTo(Document::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }
}

