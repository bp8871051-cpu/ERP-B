<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'depreciation_method',
        'useful_life_years',
        'salvage_percentage',
        'description',
        'is_active',
    ];

    protected $casts = [
        'useful_life_years' => 'integer',
        'salvage_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function assets() { return $this->hasMany(Asset::class, 'category_id'); }
}
