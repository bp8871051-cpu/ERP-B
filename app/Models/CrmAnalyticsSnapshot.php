<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmAnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $table = 'crm_analytics_snapshots';

    protected $fillable = [
        'company_id',
        'snapshot_date',
        'total_contacts',
        'total_leads',
        'qualified_leads',
        'open_deals',
        'won_deals',
        'lost_deals',
        'pipeline_value',
        'expected_revenue',
        'conversion_rate',
        'metrics',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'pipeline_value' => 'decimal:2',
        'expected_revenue' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'metrics' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
