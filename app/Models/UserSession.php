<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $table = 'user_sessions';

    protected $fillable = [
        'user_id',
        'session_token',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'location',
        'last_activity_at',
        'is_current',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
