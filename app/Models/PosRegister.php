<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'name',
        'register_code',
        'status',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function sessions() { return $this->hasMany(PosRegisterSession::class); }
    public function activeSession() { return $this->hasOne(PosRegisterSession::class)->where('status', 'open')->latestOfMany(); }
}
