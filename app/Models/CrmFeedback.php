<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_feedback';

    protected $fillable = [
        'company_id',
        'customer_id',
        'contact_id',
        'deal_id',
        'rating',
        'category',
        'feedback_text',
        'status',
        'assigned_to',
        'resolution',
        'resolved_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function deal()
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
