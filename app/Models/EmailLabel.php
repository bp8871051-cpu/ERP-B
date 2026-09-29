<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'color',
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
        return $this->belongsToMany(Email::class, 'email_label_pivot', 'label_id', 'email_id');
    }
}
