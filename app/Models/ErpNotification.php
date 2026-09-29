<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpNotification extends Model
{
    use HasFactory;

    protected $table = 'erp_notifications';

    protected $fillable = [
        'company_id',
        'user_id',
        'type', // chat, call, calendar, email, file, note, task, workflow
        'title',
        'message',
        'link',
        'icon',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
