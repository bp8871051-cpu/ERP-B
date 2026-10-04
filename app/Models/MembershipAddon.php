<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipAddon extends Model
{
    use HasFactory;

    protected $table = 'membership_addons';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'price',
        'billing_cycle',
        'quantity_limit',
        'feature_key',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity_limit' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
}
