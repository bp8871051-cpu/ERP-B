<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseCategory extends Model
{
    use HasFactory;

    protected $table = 'knowledge_base_categories';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'status',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function articles() { return $this->hasMany(KnowledgeBaseArticle::class, 'category_id'); }
}
