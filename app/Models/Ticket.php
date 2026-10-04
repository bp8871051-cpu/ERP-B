<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'assigned_agent_id',
        'department_id',
        'category_id',
        'sla_policy_id',
        'ticket_number',
        'subject',
        'description',
        'priority',
        'status',
        'source',
        'first_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'resolved_at',
        'closed_at',
        'sla_status',
        'sla_paused_at',
        'sla_paused_minutes',
        'tags',
        'merged_into_ticket_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'first_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_paused_at' => 'datetime',
        'sla_paused_minutes' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function agent() { return $this->belongsTo(User::class, 'assigned_agent_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function category() { return $this->belongsTo(TicketCategory::class, 'category_id'); }
    public function slaPolicy() { return $this->belongsTo(SupportSlaPolicy::class, 'sla_policy_id'); }
    public function messages() { return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc'); }
    public function slaLogs() { return $this->hasMany(SupportSlaLog::class); }
    public function mergedInto() { return $this->belongsTo(Ticket::class, 'merged_into_ticket_id'); }
}
