<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'ticket_id',
        'user_id',
        'sender_type',
        'message_type',
        'message',
        'attachment_name',
        'attachment_url',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function user() { return $this->belongsTo(User::class); }
}
