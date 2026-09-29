<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'email',
        'provider', // smtp, gmail, office365
        'incoming_host',
        'incoming_port',
        'outgoing_host',
        'outgoing_port',
        'credentials',
        'is_default',
        'status',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_default' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emails()
    {
        return $this->hasMany(Email::class);
    }
}
