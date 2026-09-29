<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmLeadScore extends Model
{
    use HasFactory;

    protected $table = 'crm_lead_scores';

    protected $fillable = [
        'lead_id',
        'rule_name',
        'points',
        'description',
        'evaluated_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'evaluated_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}
