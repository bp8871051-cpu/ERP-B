<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosPrintSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'receipt_width',
        'show_logo',
        'company_name',
        'company_address',
        'company_phone',
        'company_gstin',
        'show_gst',
        'show_sku',
        'show_barcode',
        'show_qr',
        'header_text',
        'footer_text',
        'thank_you_message',
        'auto_print',
        'print_copies',
    ];

    protected $appends = ['show_company_details'];

    public function getShowCompanyDetailsAttribute()
    {
        return !empty($this->attributes['company_name']);
    }

    public function setShowCompanyDetailsAttribute($value)
    {
        // virtual field for backward compatibility
    }

    public function setReceiptWidthAttribute($value)
    {
        $this->attributes['receipt_width'] = (int) str_replace('mm', '', (string)$value) ?: 80;
    }

    protected $casts = [
        'receipt_width' => 'integer',
        'show_logo' => 'boolean',
        'show_gst' => 'boolean',
        'show_sku' => 'boolean',
        'show_barcode' => 'boolean',
        'show_qr' => 'boolean',
        'auto_print' => 'boolean',
        'print_copies' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
}
