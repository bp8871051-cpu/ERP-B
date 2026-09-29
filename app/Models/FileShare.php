<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'file_id',
        'folder_id',
        'shared_by',
        'shared_with_user_id',
        'permission', // view, download, edit
        'share_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(FileItem::class, 'file_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function sharer()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
