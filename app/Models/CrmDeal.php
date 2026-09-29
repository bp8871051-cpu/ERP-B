<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmDeal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_deals';

    protected $fillable = [
        'company_id',
        'customer_id',
        'contact_id',
        'lead_id',
        'pipeline_id',
        'stage_id',
        'name',
        'value',
        'currency',
        'probability',
        'expected_revenue',
        'expected_close_date',
        'owner_id',
        'status',
        'lost_reason',
        'won_at',
        'lost_at',
        'sales_order_id',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_revenue' => 'decimal:2',
        'expected_close_date' => 'date',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function pipeline()
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(CrmPipelineStage::class, 'stage_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function items()
    {
        return $this->hasMany(CrmDealItem::class, 'deal_id');
    }

    public function stageHistory()
    {
        return $this->hasMany(CrmDealStageHistory::class, 'deal_id')->latest();
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'deal_id');
    }

    public function tasks()
    {
        return $this->hasMany(CrmTask::class, 'deal_id');
    }

    public function calls()
    {
        return $this->hasMany(CrmCall::class, 'deal_id');
    }

    public function meetings()
    {
        return $this->hasMany(CrmMeeting::class, 'deal_id');
    }

    public function emails()
    {
        return $this->hasMany(CrmEmail::class, 'deal_id');
    }

    public function notes()
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }

    public function feedback()
    {
        return $this->hasMany(CrmFeedback::class, 'deal_id');
    }
}
