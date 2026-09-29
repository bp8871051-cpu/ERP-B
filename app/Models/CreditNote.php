<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'credit_note_number',
        'date',
        'reason',
        'subtotal',
        'tax',
        'total',
        'status',
        'restock_inventory',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'restock_inventory' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(CreditNoteItem::class); }
}
