<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvancePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'advance_payments';

    protected $fillable = [
        'company_id',
        'advance_number',
        'title',
        'description',
        'advance_type', // employees, vendor, project
        'employee_id',
        'vendor_id',
        'project_id',
        'project_amount',
        'advance_date',
        'expected_settlement_date',
        'purpose',
        'note',
        'status', // pending, approved, settled, rejected
        'approval_level', // manager, finance, director, completed
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'project_amount' => 'decimal:2',
        'advance_date' => 'date',
        'expected_settlement_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($advance) {
            if (empty($advance->advance_number)) {
                $advance->advance_number = 'ADV-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

