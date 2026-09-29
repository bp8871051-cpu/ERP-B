<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'company_id',
        'customer_id',
        'first_name',
        'last_name',
        'company_name',
        'job_title',
        'email',
        'secondary_email',
        'phone',
        'whatsapp',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'contact_type',
        'lead_source_id',
        'owner_id',
        'tags',
        'notes',
        'status',
        'last_contacted_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'last_contacted_at' => 'datetime',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function leadSource()
    {
        return $this->belongsTo(CrmLeadSource::class, 'lead_source_id');
    }

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'contact_id');
    }

    public function deals()
    {
        return $this->hasMany(CrmDeal::class, 'contact_id');
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'contact_id');
    }

    public function tasks()
    {
        return $this->hasMany(CrmTask::class, 'contact_id');
    }

    public function calls()
    {
        return $this->hasMany(CrmCall::class, 'contact_id');
    }

    public function meetings()
    {
        return $this->hasMany(CrmMeeting::class, 'contact_id');
    }

    public function emails()
    {
        return $this->hasMany(CrmEmail::class, 'contact_id');
    }

    public function notes()
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }

    public function feedback()
    {
        return $this->hasMany(CrmFeedback::class, 'contact_id');
    }
}
