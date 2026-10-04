<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipUsage extends Model
{
    use HasFactory;

    protected $table = 'membership_usage';

    protected $fillable = [
        'membership_id',
        'metric',
        'used_value',
        'limit_value',
    ];

    protected $casts = [
        'used_value' => 'integer',
        'limit_value' => 'integer',
    ];

    public function membership() { return $this->belongsTo(Membership::class); }
}
