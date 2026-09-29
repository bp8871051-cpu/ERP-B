<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'can_view',
        'can_edit',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function file()
    {
        return $this->belongsTo(FileItem::class, 'file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
