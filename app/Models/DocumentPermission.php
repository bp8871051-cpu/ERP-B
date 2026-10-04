<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'role',
        'department_id',
        'can_view',
        'can_upload',
        'can_edit',
        'can_delete',
        'can_download',
        'can_share',
        'can_approve',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_upload' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_download' => 'boolean',
        'can_share' => 'boolean',
        'can_approve' => 'boolean',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function department() { return $this->belongsTo(Department::class); }
}
