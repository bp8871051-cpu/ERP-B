<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'pos_order_id',
        'receipt_number',
        'receipt_payload',
        'print_count',
        'sent_email',
        'sent_whatsapp',
        'customer_email',
        'customer_phone',
    ];

    protected $casts = [
        'receipt_payload' => 'array',
        'print_count' => 'integer',
        'sent_email' => 'boolean',
        'sent_whatsapp' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function order() { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
}
