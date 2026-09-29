<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'events';

    protected $fillable = [
        'company_id',
        'calendar_id',
        'creator_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'timezone',
        'location',
        'meeting_link',
        'color',
        'category', // meeting, task, workflow, schedule, personal
        'status', // scheduled, completed, cancelled
        'is_all_day',
        'is_recurring',
        'recurrence_rule',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_all_day' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function attendees()
    {
        return $this->hasMany(EventAttendee::class, 'event_id');
    }

    public function reminders()
    {
        return $this->hasMany(EventReminder::class, 'event_id');
    }

    public function recurrences()
    {
        return $this->hasMany(EventRecurrence::class, 'event_id');
    }
}
