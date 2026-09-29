<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignEvent extends Model
{
    use HasFactory;

    protected $table = 'crm_campaign_events';

    protected $fillable = [
        'campaign_id',
        'event_type',
        'contact_id',
        'lead_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(CrmCampaign::class, 'campaign_id');
    }

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}
