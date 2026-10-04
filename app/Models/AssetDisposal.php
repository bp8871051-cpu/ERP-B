<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'disposal_type',
        'disposal_date',
        'book_value',
        'sale_value',
        'loss_gain',
        'reason',
        'approved_by',
        'approved_at',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'approved_at' => 'datetime',
        'book_value' => 'decimal:2',
        'sale_value' => 'decimal:2',
        'loss_gain' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
