<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCompliance extends Model
{
    use HasFactory;

    protected $table = 'document_compliance';

    protected $fillable = [
        'company_id',
        'document_id',
        'compliance_type',
        'title',
        'authority',
        'document_number',
        'issue_date',
        'expiry_date',
        'responsible_person_id',
        'department_id',
        'status',
        'notes',
        'attachment_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function document() { return $this->belongsTo(Document::class); }
    public function responsiblePerson() { return $this->belongsTo(Employee::class, 'responsible_person_id'); }
    public function department() { return $this->belongsTo(Department::class); }
}
