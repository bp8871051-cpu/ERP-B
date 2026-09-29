<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'assigned_agent_id', 'category_id',
        'ticket_number', 'subject', 'description', 'priority', 'status'
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function agent() { return $this->belongsTo(User::class, 'assigned_agent_id'); }
    public function category() { return $this->belongsTo(TicketCategory::class, 'category_id'); }
    public function messages() { return $this->hasMany(TicketMessage::class); }
}
