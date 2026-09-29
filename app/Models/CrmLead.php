<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'company_id',
        'contact_id',
        'customer_id',
        'name',
        'company_name',
        'email',
        'phone',
        'job_title',
        'website',
        'lead_source_id',
        'campaign_id',
        'owner_id',
        'industry',
        'company_size',
        'budget',
        'expected_value',
        'expected_close_date',
        'score',
        'score_category',
        'status',
        'lost_reason',
        'notes',
        'tags',
        'converted_at',
        'converted_by',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'expected_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'score' => 'integer',
        'tags' => 'array',
        'converted_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(CrmLeadSource::class, 'lead_source_id');
    }

    public function campaign()
    {
        return $this->belongsTo(CrmCampaign::class, 'campaign_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function convertedByUser()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function scoreItems()
    {
        return $this->hasMany(CrmLeadScore::class, 'lead_id');
    }

    public function deals()
    {
        return $this->hasMany(CrmDeal::class, 'lead_id');
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'lead_id');
    }

    public function tasks()
    {
        return $this->hasMany(CrmTask::class, 'lead_id');
    }

    public function calls()
    {
        return $this->hasMany(CrmCall::class, 'lead_id');
    }

    public function meetings()
    {
        return $this->hasMany(CrmMeeting::class, 'lead_id');
    }

    public function emails()
    {
        return $this->hasMany(CrmEmail::class, 'lead_id');
    }

    public function notes()
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }
}
