<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_request_id',
        'user_id',
        'comment',
    ];

    public function request()
    {
        return $this->belongsTo(WorkflowRequest::class, 'workflow_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
