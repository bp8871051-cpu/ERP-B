<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'request_number',
        'requested_by',
        'request_type',
        'project_id',
        'ticket_id',
        'warehouse_id',
        'status',
        'priority',
        'required_date',
        'site_location',
        'notes',
        'issued_by',
        'issued_at',
        'rejection_reason',
    ];

    protected $casts = [
        'required_date' => 'date',
        'issued_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }
}
