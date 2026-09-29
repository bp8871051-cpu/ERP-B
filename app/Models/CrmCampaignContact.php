<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignContact extends Model
{
    use HasFactory;

    protected $table = 'crm_campaign_contacts';

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'lead_id',
        'customer_id',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
