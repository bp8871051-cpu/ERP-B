<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmMeeting extends Model
{
    use HasFactory;

    protected $table = 'crm_meetings';

    protected $fillable = [
        'company_id',
        'user_id',
        'contact_id',
        'lead_id',
        'deal_id',
        'title',
        'description',
        'meeting_date',
        'start_time',
        'end_time',
        'location',
        'meeting_link',
        'participants',
        'status',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'participants' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function deal()
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }
}
