<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'shared_with_user_id',
        'permission', // view, edit
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
