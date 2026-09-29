<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'client_id', 'name', 'description', 'start_date', 'end_date',
        'budget', 'spent', 'status', 'progress'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function client() { return $this->belongsTo(Customer::class, 'client_id'); }
    public function members() { return $this->hasMany(ProjectMember::class); }
    public function tasks() { return $this->hasMany(Task::class); }
    public function milestones() { return $this->hasMany(Milestone::class); }
    public function timesheets() { return $this->hasMany(Timesheet::class); }
}
