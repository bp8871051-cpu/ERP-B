<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLayoutPreference extends Model
{
    use HasFactory;

    protected $table = 'user_layout_preferences';

    protected $fillable = [
        'user_id',
        'sidebar_mode',       // 'default', 'mini', 'hover', 'hidden'
        'menu_behavior',      // 'click', 'hover'
        'content_width',      // 'default', 'full'
        'direction',          // 'ltr', 'rtl'
        'sidebar_visibility', // 'visible', 'hidden'
        'sidebar_state',      // 'expanded', 'collapsed'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'sidebar_mode' => 'string',
        'menu_behavior' => 'string',
        'content_width' => 'string',
        'direction' => 'string',
        'sidebar_visibility' => 'string',
        'sidebar_state' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
