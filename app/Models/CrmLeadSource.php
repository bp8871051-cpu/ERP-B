<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmLeadSource extends Model
{
    use HasFactory;

    protected $table = 'crm_lead_sources';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'lead_source_id');
    }

    public function contacts()
    {
        return $this->hasMany(CrmContact::class, 'lead_source_id');
    }
}
