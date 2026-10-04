<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'support_contact_messages';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'source',
        'status',
        'assigned_to_user_id',
        'ticket_id',
        'reply_message',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function assignedAgent() { return $this->belongsTo(User::class, 'assigned_to_user_id'); }
    public function ticket() { return $this->belongsTo(Ticket::class); }
}
