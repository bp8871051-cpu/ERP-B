<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmNote extends Model
{
    use HasFactory;

    protected $table = 'crm_notes';

    protected $fillable = [
        'company_id',
        'user_id',
        'notable_type',
        'notable_id',
        'title',
        'content',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notable()
    {
        return $this->morphTo();
    }
}
