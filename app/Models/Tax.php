<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'rate',
        'is_compound',
        'description',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
