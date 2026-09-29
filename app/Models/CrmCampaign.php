<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_campaigns';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'description',
        'start_date',
        'end_date',
        'owner_id',
        'budget',
        'status',
        'target_audience',
        'total_audience',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'converted_count',
        'revenue',
        'cost',
        'roi',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'revenue' => 'decimal:2',
        'cost' => 'decimal:2',
        'roi' => 'decimal:2',
        'target_audience' => 'array',
        'total_audience' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'converted_count' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function audiences()
    {
        return $this->hasMany(CrmCampaignAudience::class, 'campaign_id');
    }

    public function campaignContacts()
    {
        return $this->hasMany(CrmCampaignContact::class, 'campaign_id');
    }

    public function events()
    {
        return $this->hasMany(CrmCampaignEvent::class, 'campaign_id');
    }

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'campaign_id');
    }

    public function notes()
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }
}
