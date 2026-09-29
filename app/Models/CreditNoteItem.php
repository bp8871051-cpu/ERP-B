<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_note_id',
        'product_id',
        'quantity',
        'unit_price',
        'tax',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function creditNote() { return $this->belongsTo(CreditNote::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
