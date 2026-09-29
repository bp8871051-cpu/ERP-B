<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmEmail extends Model
{
    use HasFactory;

    protected $table = 'crm_emails';

    protected $fillable = [
        'company_id',
        'user_id',
        'contact_id',
        'lead_id',
        'deal_id',
        'from_email',
        'to_email',
        'cc_email',
        'bcc_email',
        'subject',
        'body_html',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
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
