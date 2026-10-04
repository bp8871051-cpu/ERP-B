<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'name',
        'file_path',
        'document_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function asset() { return $this->belongsTo(Asset::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
