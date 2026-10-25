<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSiteVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'ticket_id',
        'engineer_id',
        'customer_id',
        'customer_name',
        'site_address',
        'contact_phone',
        'visit_date',
        'visit_time',
        'service_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function engineer()
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
