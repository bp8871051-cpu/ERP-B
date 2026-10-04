<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteRequest extends Model
{
    use HasFactory;

    protected $table = 'delete_requests';

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'record_id',
        'record_title',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function requester() { return $this->belongsTo(User::class, 'user_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
