<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'employee_id',
        'department_id',
        'location_id',
        'assigned_by',
        'assigned_date',
        'expected_return_date',
        'actual_return_date',
        'condition',
        'status',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function location() { return $this->belongsTo(AssetLocation::class); }
    public function assigner() { return $this->belongsTo(User::class, 'assigned_by'); }
}
