<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'document_id',
        'title',
        'policy_number',
        'category',
        'version',
        'owner_id',
        'department_id',
        'effective_date',
        'review_date',
        'expiry_date',
        'status',
        'summary',
        'attachment_path',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'review_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function document() { return $this->belongsTo(Document::class); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function department() { return $this->belongsTo(Department::class); }
}
