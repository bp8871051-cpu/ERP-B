<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'shared_by',
        'share_token',
        'share_type',
        'shared_with_user_id',
        'shared_with_department_id',
        'shared_with_role',
        'password_hash',
        'expires_at',
        'allow_download',
        'access_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'allow_download' => 'boolean',
        'access_count' => 'integer',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function sharer() { return $this->belongsTo(User::class, 'shared_by'); }
    public function recipientUser() { return $this->belongsTo(User::class, 'shared_with_user_id'); }
    public function recipientDepartment() { return $this->belongsTo(Department::class, 'shared_with_department_id'); }
}
