<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpDocumentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
    ];

    public function documents()
    {
        return $this->hasMany(HelpDocument::class, 'category_id')->orderBy('sort_order', 'asc');
    }
}
