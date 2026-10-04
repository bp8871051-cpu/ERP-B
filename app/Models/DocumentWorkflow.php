<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function steps() { return $this->hasMany(DocumentWorkflowStep::class, 'workflow_id')->orderBy('sequence'); }
}
