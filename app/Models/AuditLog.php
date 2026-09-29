<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'action', 'module', 'description', 'ip_address', 'user_agent', 'old_values', 'new_values'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($log) {
            if (empty($log->description)) {
                $log->description = ($log->action ?? 'action') . ' on ' . ($log->module ?? 'inventory');
            }
        });
    }
}
