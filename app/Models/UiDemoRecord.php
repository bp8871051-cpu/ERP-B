<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UiDemoRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ui_demo_records';

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'email',
        'role',
        'department',
        'status',
        'salary',
        'joined_date',
        'avatar',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'joined_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
