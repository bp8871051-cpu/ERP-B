<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'building',
        'floor',
        'room',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function assets() { return $this->hasMany(Asset::class, 'location_id'); }
}
