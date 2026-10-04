<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipAddonItem extends Model
{
    use HasFactory;

    protected $table = 'membership_addon_items';

    protected $fillable = [
        'membership_id',
        'addon_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function membership() { return $this->belongsTo(Membership::class); }
    public function addon() { return $this->belongsTo(MembershipAddon::class, 'addon_id'); }
}
