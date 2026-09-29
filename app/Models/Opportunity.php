<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'assigned_to', 'title', 'stage', 'probability', 'expected_revenue', 'close_date'
    ];

    protected $casts = [
        'expected_revenue' => 'decimal:2',
        'close_date' => 'date',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
}
