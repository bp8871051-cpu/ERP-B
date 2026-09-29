<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'quote_number', 'date', 'expiry_date', 'total', 'status'];

    protected $casts = [
        'date' => 'date',
        'expiry_date' => 'date',
        'total' => 'decimal:2',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
}
