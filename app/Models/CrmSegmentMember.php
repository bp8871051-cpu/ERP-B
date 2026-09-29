<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmSegmentMember extends Model
{
    use HasFactory;

    protected $table = 'crm_segment_members';

    protected $fillable = [
        'segment_id',
        'customer_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function segment()
    {
        return $this->belongsTo(CrmCustomerSegment::class, 'segment_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
