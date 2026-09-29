<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'status',
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->slug) && !empty($category->name)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function parent() { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id'); }
    public function products() { return $this->hasMany(Product::class); }
}
