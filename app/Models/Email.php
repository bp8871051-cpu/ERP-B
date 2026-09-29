<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'email_account_id',
        'user_id',
        'thread_id',
        'folder', // inbox, sent, drafts, starred, important, trash, spam
        'from_email',
        'from_name',
        'subject',
        'body_html',
        'body_text',
        'is_read',
        'is_starred',
        'is_important',
        'is_draft',
        'sent_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_important' => 'boolean',
        'is_draft' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(EmailAccount::class, 'email_account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipients()
    {
        return $this->hasMany(EmailRecipient::class);
    }

    public function attachments()
    {
        return $this->hasMany(EmailAttachment::class);
    }

    public function labels()
    {
        return $this->belongsToMany(EmailLabel::class, 'email_label_pivot', 'email_id', 'label_id');
    }
}
