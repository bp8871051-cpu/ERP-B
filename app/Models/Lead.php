<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'assigned_to', 'title', 'first_name', 'last_name',
        'email', 'phone', 'company', 'source', 'status', 'deal_value', 'notes'
    ];

    protected $casts = [
        'deal_value' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function activities() { return $this->morphMany(Activity::class, 'subjectable'); }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
