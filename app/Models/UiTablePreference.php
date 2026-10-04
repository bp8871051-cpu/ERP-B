<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UiTablePreference extends Model
{
    use HasFactory;

    protected $table = 'ui_table_preferences';

    protected $fillable = [
        'user_id',
        'table_key',
        'visible_columns',
        'density',
        'per_page',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'per_page' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
