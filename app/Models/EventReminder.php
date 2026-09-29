<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'minutes_before',
        'type', // notification, email
        'is_sent',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }
}
