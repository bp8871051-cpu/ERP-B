<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignAudience extends Model
{
    use HasFactory;

    protected $table = 'crm_campaign_audiences';

    protected $fillable = [
        'campaign_id',
        'filter_criteria',
        'total_count',
    ];

    protected $casts = [
        'filter_criteria' => 'array',
        'total_count' => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(CrmCampaign::class, 'campaign_id');
    }
}
