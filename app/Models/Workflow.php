<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'module',
        'trigger',
        'status',
        'created_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('step_order', 'asc');
    }

    public function requests()
    {
        return $this->hasMany(WorkflowRequest::class);
    }
}
