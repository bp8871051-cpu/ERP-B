<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'payslip_number',
        'file_path',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
