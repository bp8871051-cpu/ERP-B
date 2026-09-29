<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'parent_id',
        'body',
        'type', // text, image, file, voice, system
        'is_edited',
        'is_pinned',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }
}
