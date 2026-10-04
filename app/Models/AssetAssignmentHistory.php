<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignmentHistory extends Model
{
    use HasFactory;

    protected $table = 'asset_assignment_histories';

    protected $fillable = [
        'company_id',
        'asset_id',
        'previous_holder',
        'new_holder',
        'previous_location',
        'new_location',
        'assigned_date',
        'returned_date',
        'condition',
        'assigned_by',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'returned_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
}
