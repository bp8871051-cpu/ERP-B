<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmPipelineStage extends Model
{
    use HasFactory;

    protected $table = 'crm_pipeline_stages';

    protected $fillable = [
        'pipeline_id',
        'name',
        'stage_order',
        'slug',
        'order',
        'probability',
        'color',
        'is_won',
        'is_lost',
    ];

    protected $casts = [
        'stage_order' => 'integer',
        'order' => 'integer',
        'probability' => 'integer',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
    ];

    public function pipeline()
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function deals()
    {
        return $this->hasMany(CrmDeal::class, 'stage_id');
    }
}
