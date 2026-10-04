<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $table = 'api_keys';

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'key',
        'secret_hash',
        'scopes',
        'last_used_at',
        'expires_at',
        'is_revoked',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class); }
}
