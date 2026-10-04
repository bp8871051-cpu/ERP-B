<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'parent_id',
        'department_id',
        'name',
        'slug',
        'color',
        'created_by',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function parent() { return $this->belongsTo(DocumentFolder::class, 'parent_id'); }
    public function children() { return $this->hasMany(DocumentFolder::class, 'parent_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function documents() { return $this->hasMany(Document::class, 'folder_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
