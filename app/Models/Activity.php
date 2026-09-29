<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'subjectable_type', 'subjectable_id', 'type', 'subject', 'description', 'scheduled_at', 'status'];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function subjectable() { return $this->morphTo(); }
}
