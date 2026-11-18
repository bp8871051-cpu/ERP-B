<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmDuplicateLeadRequest extends Model
{
    use HasFactory;

    protected $table = 'crm_duplicate_lead_requests';

    protected $fillable = [
        'company_id',
        'requested_by',
        'existing_lead_id',
        'matched_field',
        'matched_value',
        'lead_data',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'created_lead_id',
    ];

    protected $casts = [
        'lead_data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function existingLead()
    {
        return $this->belongsTo(CrmLead::class, 'existing_lead_id');
    }

    public function createdLead()
    {
        return $this->belongsTo(CrmLead::class, 'created_lead_id');
    }
}
