<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmCustomerSegment extends Model
{
    use HasFactory;

    protected $table = 'crm_customer_segments';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'rules',
        'color',
        'status',
    ];

    protected $casts = [
        'rules' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function members()
    {
        return $this->hasMany(CrmSegmentMember::class, 'segment_id');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'crm_segment_members', 'segment_id', 'customer_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }
}
