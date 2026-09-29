<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRecurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'frequency', // daily, weekly, monthly, yearly
        'interval',
        'until',
    ];

    protected $casts = [
        'until' => 'date',
        'interval' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }
}
