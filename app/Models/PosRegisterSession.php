<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRegisterSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'pos_register_id',
        'cashier_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'difference',
        'total_sales',
        'total_transactions',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_transactions' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function register() { return $this->belongsTo(PosRegister::class, 'pos_register_id'); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function orders() { return $this->hasMany(PosOrder::class, 'register_session_id'); }
}
