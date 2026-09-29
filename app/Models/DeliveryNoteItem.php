<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_note_id',
        'product_id',
        'ordered_quantity',
        'delivered_quantity',
        'remaining_quantity',
    ];

    public function deliveryNote() { return $this->belongsTo(DeliveryNote::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
