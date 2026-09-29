<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'caller_id',
        'receiver_id',
        'room_id',
        'type', // voice, video, internal, external
        'direction', // incoming, outgoing
        'status', // completed, missed, rejected, busy, cancelled
        'start_time',
        'end_time',
        'duration',
        'provider',
        'recording_url',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function caller()
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function participants()
    {
        return $this->hasMany(CallParticipant::class);
    }

    public function logs()
    {
        return $this->hasMany(CallLog::class);
    }
}
