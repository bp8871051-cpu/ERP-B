<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciationSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'schedule_type',
        'period_number',
        'period_date',
        'beginning_value',
        'depreciation_amount',
        'ending_value',
    ];

    protected $casts = [
        'period_date' => 'date',
        'beginning_value' => 'decimal:2',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'ending_value' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
}
