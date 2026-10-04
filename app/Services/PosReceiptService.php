<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PosOrder;
use App\Models\PosPrintSetting;
use Carbon\Carbon;

class PosReceiptService
{
    /**
     * Generate structured receipt JSON data for direct rendering or thermal printing.
     */
    public function generateReceiptData(PosOrder $order): array
    {
        $order->loadMissing(['items.product', 'payments', 'customer', 'cashier', 'company', 'invoice']);
        $company = $order->company ?: Company::find($order->company_id);
        $settings = PosPrintSetting::where('company_id', $order->company_id)->first();

        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->product_name ?: ($item->product?->name ?? 'Product Item'),
                'sku' => $item->sku ?: ($item->product?->sku ?? ''),
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount_amount,
                'tax' => (float) $item->tax_amount,
                'total' => (float) $item->total_price,
            ];
        });

        $payments = $order->payments->map(function ($p) {
            return [
                'method' => strtoupper($p->payment_method),
                'amount' => (float) $p->amount,
                'reference' => $p->reference_no,
            ];
        });

        return [
            'company' => [
                'name' => $company?->name ?? 'Falcon Enterprise ERP',
                'address' => $company?->address ?? 'Corporate Park, Level 4, Tech City',
                'phone' => $company?->phone ?? '+91 98765 43210',
                'email' => $company?->email ?? 'info@falconerp.com',
                'gstin' => $company?->tax_number ?? '27AAACG0123M1Z9',
                'logo' => $company?->logo,
            ],
            'order' => [
                'order_number' => $order->order_number,
                'invoice_number' => $order->invoice?->invoice_number ?? ('INV-' . $order->order_number),
                'date' => $order->created_at->format('d M Y, h:i A'),
                'cashier' => $order->cashier?->name ?? 'Admin Cashier',
                'customer' => $order->customer?->name ?? 'Walk-in Customer',
                'customer_phone' => $order->customer?->phone,
            ],
            'items' => $items,
            'summary' => [
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) $order->discount_amount,
                'tax' => (float) $order->tax_amount,
                'round_off' => (float) $order->round_off,
                'grand_total' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'change_amount' => (float) $order->change_amount,
            ],
            'payments' => $payments,
            'settings' => [
                'width' => $settings?->receipt_width ?? '80mm',
                'show_logo' => $settings?->show_logo ?? true,
                'show_gst' => $settings?->show_gst ?? true,
                'show_sku' => $settings?->show_sku ?? true,
                'show_barcode' => $settings?->show_barcode ?? true,
                'show_qr' => $settings?->show_qr ?? true,
                'footer_text' => $settings?->footer_text ?? 'Goods once sold will only be exchanged within 7 days with valid receipt.',
                'thank_you_message' => $settings?->thank_you_message ?? 'Thank you for shopping with us! Visit again.',
            ],
        ];
    }
}
