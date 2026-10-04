<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'period_date',
        'depreciation_method',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value_ending',
        'created_by',
    ];

    protected $casts = [
        'period_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value_ending' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function calculator() { return $this->belongsTo(User::class, 'calculated_by'); }
}
