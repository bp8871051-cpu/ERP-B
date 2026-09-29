<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'files';

    protected $fillable = [
        'company_id',
        'user_id',
        'folder_id',
        'name',
        'disk',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'is_favorite',
        'is_recent',
        'download_count',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'is_recent' => 'boolean',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function versions()
    {
        return $this->hasMany(FileVersion::class, 'file_id');
    }

    public function shares()
    {
        return $this->hasMany(FileShare::class, 'file_id');
    }

    public function permissions()
    {
        return $this->hasMany(FilePermission::class, 'file_id');
    }
}
