<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmPipeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_pipelines';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function stages()
    {
        return $this->hasMany(CrmPipelineStage::class, 'pipeline_id')->orderBy('stage_order');
    }

    public function deals()
    {
        return $this->hasMany(CrmDeal::class, 'pipeline_id');
    }
}
