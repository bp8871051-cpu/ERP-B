<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'version_number',
        'file_path',
        'file_size',
        'created_by',
    ];

    public function file()
    {
        return $this->belongsTo(FileItem::class, 'file_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
