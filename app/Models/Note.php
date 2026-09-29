<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'content',
        'checklist',
        'color',
        'is_pinned',
        'is_archived',
        'is_favorite',
    ];

    protected $casts = [
        'checklist' => 'array',
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
        'is_favorite' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'note_tags', 'note_id', 'tag_id');
    }

    public function attachments()
    {
        return $this->hasMany(NoteAttachment::class);
    }

    public function shares()
    {
        return $this->hasMany(NoteShare::class);
    }
}
