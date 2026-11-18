<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftwareLicense extends Model
{
    use HasFactory;

    protected $table = 'software_licenses';

    protected $fillable = [
        'company_id',
        'license_key',
        'software_name',
        'license_type',
        'customer_id',
        'customer_name',
        'seats_total',
        'seats_allocated',
        'issued_date',
        'expiry_date',
        'status',
        'cost',
        'vendor',
        'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'seats_total' => 'integer',
        'seats_allocated' => 'integer',
        'cost' => 'decimal:2',
    ];

    public function allocations()
    {
        return $this->hasMany(LicenseAllocation::class, 'license_id');
    }
}

