<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'knowledge_base_articles';

    protected $fillable = [
        'company_id',
        'category_id',
        'author_id',
        'title',
        'slug',
        'description',
        'content',
        'featured_image',
        'status',
        'visibility',
        'tags',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id'); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
