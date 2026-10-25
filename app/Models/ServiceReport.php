<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'report_number',
        'ticket_id',
        'engineer_id',
        'customer_id',
        'customer_name',
        'equipment_name',
        'serial_number',
        'work_performed',
        'hours_spent',
        'parts_used',
        'recommendations',
        'customer_confirmation_status',
        'sign_off_notes',
        'completed_at',
    ];

    protected $casts = [
        'parts_used' => 'array',
        'hours_spent' => 'decimal:2',
        'completed_at' => 'datetime',
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
