<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_activities';

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'subject',
        'description',
        'contact_id',
        'lead_id',
        'deal_id',
        'customer_id',
        'due_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function deal()
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
