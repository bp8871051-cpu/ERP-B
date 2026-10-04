<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'folder_id',
        'category_id',
        'department_id',
        'owner_id',
        'uploaded_by',
        'document_number',
        'document_name',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'file_hash',
        'disk',
        'current_version',
        'confidentiality',
        'status',
        'issue_date',
        'expiry_date',
        'description',
    ];

    protected $appends = ['name', 'file_type', 'is_archived'];

    public function getNameAttribute()
    {
        return $this->attributes['document_name'] ?? ($this->attributes['name'] ?? '');
    }

    public function setNameAttribute($value)
    {
        $this->attributes['document_name'] = $value;
    }

    public function getFileTypeAttribute()
    {
        return $this->attributes['document_type'] ?? ($this->attributes['file_type'] ?? 'pdf');
    }

    public function setFileTypeAttribute($value)
    {
        $this->attributes['document_type'] = $value;
    }

    public function getIsArchivedAttribute()
    {
        return ($this->attributes['status'] ?? '') === 'Archived';
    }

    protected $casts = [
        'file_size' => 'integer',
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function folder() { return $this->belongsTo(DocumentFolder::class, 'folder_id'); }
    public function category() { return $this->belongsTo(DocumentCategory::class, 'category_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function versions() { return $this->hasMany(DocumentVersion::class); }
    public function permissions() { return $this->hasMany(DocumentPermission::class); }
    public function shares() { return $this->hasMany(DocumentShare::class); }
    public function approvals() { return $this->hasMany(DocumentApproval::class); }
    public function comments() { return $this->hasMany(DocumentComment::class); }
    public function compliance() { return $this->hasOne(DocumentCompliance::class); }
    public function policy() { return $this->hasOne(DocumentPolicy::class); }
    public function auditLogs() { return $this->hasMany(DocumentAuditLog::class); }
}
