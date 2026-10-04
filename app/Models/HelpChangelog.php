<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpChangelog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'version',
        'title',
        'description',
        'release_date',
        'tag',
        'changes',
        'status',
    ];

    protected $casts = [
        'release_date' => 'date',
        'changes' => 'array',
    ];
}
