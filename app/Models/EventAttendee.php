<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'email',
        'name',
        'status', // pending, accepted, declined, tentative
    ];

    public function event()
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
