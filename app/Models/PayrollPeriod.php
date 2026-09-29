<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'payment_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
