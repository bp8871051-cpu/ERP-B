<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcNcr extends Model
{
    use HasFactory;

    protected $table = 'qc_ncrs';

    protected $fillable = [
        'company_id',
        'ncr_number',
        'inspection_id',
        'product_id',
        'reported_by',
        'title',
        'description',
        'severity',
        'status',
        'root_cause',
        'corrective_action',
        'action_assigned_to',
        'action_due_date',
        'resolved_at',
    ];

    protected $casts = [
        'action_due_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function inspection()
    {
        return $this->belongsTo(QcInspection::class, 'inspection_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'action_assigned_to');
    }
}
