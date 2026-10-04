<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    use HasFactory;

    protected $table = 'asset_maintenances';

    protected $fillable = [
        'company_id',
        'asset_id',
        'maintenance_type',
        'priority',
        'issue',
        'description',
        'vendor_id',
        'assigned_technician',
        'start_date',
        'expected_completion',
        'actual_completion',
        'estimated_cost',
        'actual_cost',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_completion' => 'date',
        'actual_completion' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
