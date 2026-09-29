<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'type', // to, cc, bcc
        'email',
        'name',
    ];

    public function email()
    {
        return $this->belongsTo(Email::class);
    }
}
