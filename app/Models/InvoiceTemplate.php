<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'logo_url',
        'header_text',
        'footer_text',
        'terms',
        'payment_instructions',
        'tax_display',
        'color_theme',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function recurringInvoices() { return $this->hasMany(RecurringInvoice::class, 'template_id'); }
}
