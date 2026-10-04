<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UiDragDropItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ui_drag_drop_items';

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'order_index',
        'assigned_to',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
