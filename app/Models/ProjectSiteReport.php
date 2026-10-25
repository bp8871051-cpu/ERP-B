<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSiteReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'project_id',
        'engineer_id',
        'report_date',
        'work_completed',
        'site_conditions',
        'blockers',
        'manpower_count',
    ];

    protected $casts = [
        'report_date' => 'date',
        'manpower_count' => 'integer',
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
