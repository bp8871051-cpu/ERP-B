<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAuditLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'document_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function document() { return $this->belongsTo(Document::class); }
    public function user() { return $this->belongsTo(User::class); }
}
