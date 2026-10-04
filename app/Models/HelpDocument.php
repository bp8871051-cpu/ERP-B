<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'sort_order',
        'read_time',
    ];

    public function category()
    {
        return $this->belongsTo(HelpDocumentCategory::class, 'category_id');
    }
}
