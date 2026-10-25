<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEngineeringIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'project_id',
        'engineer_id',
        'title',
        'description',
        'severity',
        'status',
        'mitigation_plan',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function engineer()
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }
}
