<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'category_id',
        'department_id',
        'location_id',
        'vendor_id',
        'assigned_to',
        'asset_code',
        'asset_name',
        'subcategory',
        'serial_number',
        'model_number',
        'brand',
        'purchase_date',
        'purchase_invoice',
        'purchase_cost',
        'tax_amount',
        'total_cost',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'current_book_value',
        'warranty_start',
        'warranty_end',
        'status',
        'notes',
        'image',
        'created_by',
    ];

    protected $appends = ['name', 'useful_life_years', 'accumulated_depreciation'];

    public function getAccumulatedDepreciationAttribute()
    {
        $cost = (float)($this->attributes['total_cost'] ?? ($this->attributes['purchase_cost'] ?? 0));
        $book = (float)($this->attributes['current_book_value'] ?? $cost);
        return max(0.0, $cost - $book);
    }

    public function getNameAttribute()
    {
        return $this->attributes['asset_name'] ?? ($this->attributes['name'] ?? '');
    }

    public function setNameAttribute($value)
    {
        $this->attributes['asset_name'] = $value;
    }

    public function getUsefulLifeYearsAttribute()
    {
        return isset($this->attributes['useful_life_months']) ? (int)round($this->attributes['useful_life_months'] / 12) : 5;
    }

    public function setUsefulLifeYearsAttribute($value)
    {
        $this->attributes['useful_life_months'] = ((int)$value) * 12;
    }

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'purchase_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'current_book_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'useful_life_months' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(AssetCategory::class, 'category_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function location() { return $this->belongsTo(AssetLocation::class, 'location_id'); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function assignedEmployee() { return $this->belongsTo(Employee::class, 'assigned_to'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function assignments() { return $this->hasMany(AssetAssignment::class); }
    public function currentAssignment() { return $this->hasOne(AssetAssignment::class)->where('status', 'Assigned')->latestOfMany(); }
    public function assignmentHistory() { return $this->hasMany(AssetAssignmentHistory::class); }
    public function depreciations() { return $this->hasMany(AssetDepreciation::class); }
    public function depreciationSchedules() { return $this->hasMany(AssetDepreciationSchedule::class); }
    public function maintenanceRecords() { return $this->hasMany(AssetMaintenance::class); }
    public function disposals() { return $this->hasMany(AssetDisposal::class); }
    public function documents() { return $this->hasMany(AssetDocument::class); }
    public function auditLogs() { return $this->hasMany(AssetAuditLog::class); }
}
