<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'date',
        'type',
        'description',
        'applicable_departments',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'applicable_departments' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
