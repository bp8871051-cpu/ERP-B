<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmDealStageHistory extends Model
{
    use HasFactory;

    protected $table = 'crm_deal_stage_history';

    protected $fillable = [
        'deal_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'notes',
        'remarks',
    ];

    public function deal()
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    public function fromStage()
    {
        return $this->belongsTo(CrmPipelineStage::class, 'from_stage_id');
    }

    public function toStage()
    {
        return $this->belongsTo(CrmPipelineStage::class, 'to_stage_id');
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
