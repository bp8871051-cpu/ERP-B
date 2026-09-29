<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_message_id',
        'is_pinned',
        'is_muted',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_muted' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
