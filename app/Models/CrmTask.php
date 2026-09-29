<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'assigned_to',
        'created_by',
        'contact_id',
        'lead_id',
        'deal_id',
        'customer_id',
        'priority',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
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
