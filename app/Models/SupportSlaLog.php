<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportSlaLog extends Model
{
    use HasFactory;

    protected $table = 'support_sla_logs';

    protected $fillable = [
        'company_id',
        'ticket_id',
        'sla_policy_id',
        'event',
        'target_minutes',
        'actual_minutes',
        'is_breached',
        'notes',
    ];

    protected $casts = [
        'is_breached' => 'boolean',
        'target_minutes' => 'integer',
        'actual_minutes' => 'integer',
    ];

    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function slaPolicy() { return $this->belongsTo(SupportSlaPolicy::class, 'sla_policy_id'); }
}
