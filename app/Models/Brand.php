<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'logo',
        'description',
        'website',
        'status',
    ];

    protected static function booted()
    {
        static::creating(function ($brand) {
            if (empty($brand->slug) && !empty($brand->name)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function products() { return $this->hasMany(Product::class); }
}
