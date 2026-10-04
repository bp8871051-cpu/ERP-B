<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'file_hash',
        'change_summary',
        'uploaded_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
