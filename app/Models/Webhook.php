<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $table = 'webhooks';

    protected $fillable = [
        'company_id',
        'name',
        'url',
        'event',
        'secret',
        'status',
        'retry_policy',
        'last_status_code',
        'last_triggered_at',
    ];

    protected $casts = [
        'last_triggered_at' => 'datetime',
        'last_status_code' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
}
