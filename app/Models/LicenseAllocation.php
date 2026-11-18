<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseAllocation extends Model
{
    use HasFactory;

    protected $table = 'license_allocations';

    protected $fillable = [
        'license_id',
        'user_id',
        'assigned_name',
        'department',
        'device_id',
        'ip_address',
        'assigned_date',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function license()
    {
        return $this->belongsTo(SoftwareLicense::class, 'license_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

