<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'short_name',
        'short_code',
        'unit_type',
        'conversion_factor',
        'status',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function products() { return $this->hasMany(Product::class); }
}
