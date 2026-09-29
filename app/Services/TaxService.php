<?php

namespace App\Services;

use App\Models\Tax;

class TaxService
{
    /**
     * Calculate authoritative tax amount for a taxable amount and tax specification
     */
    public function calculate(float $amount, $taxIdentifier, float $previousTaxes = 0.0): array
    {
        $tax = null;
        if (is_numeric($taxIdentifier)) {
            $tax = Tax::find($taxIdentifier);
        } elseif (is_string($taxIdentifier)) {
            $tax = Tax::where('code', $taxIdentifier)->orWhere('name', $taxIdentifier)->first();
        }

        if (!$tax) {
            // Treat as percentage if numeric float passed directly
            $rate = is_numeric($taxIdentifier) ? (float) $taxIdentifier : 0.0;
            $taxAmount = round(($amount * $rate) / 100, 2);
            return [
                'tax_name' => 'Custom Tax (' . $rate . '%)',
                'tax_code' => 'CUSTOM',
                'rate' => $rate,
                'taxable_amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_with_tax' => round($amount + $taxAmount, 2),
            ];
        }

        $taxableBase = $amount;
        if ($tax->is_compound) {
            $taxableBase += $previousTaxes;
        }

        $rate = (float) $tax->rate;
        $taxAmount = 0.0;

        if ($tax->type === 'fixed') {
            $taxAmount = $rate;
        } else {
            // percentage or compound
            $taxAmount = round(($taxableBase * $rate) / 100, 2);
        }

        return [
            'tax_id' => $tax->id,
            'tax_name' => $tax->name,
            'tax_code' => $tax->code,
            'rate' => $rate,
            'is_compound' => $tax->is_compound,
            'taxable_amount' => $taxableBase,
            'tax_amount' => $taxAmount,
            'total_with_tax' => round($amount + $taxAmount, 2),
        ];
    }

    /**
     * Breakdown GST into CGST and SGST (for intra-state) or IGST (for inter-state)
     */
    public function calculateGstBreakdown(float $amount, float $gstRate = 18.0, bool $isIntraState = true): array
    {
        $totalTax = round(($amount * $gstRate) / 100, 2);

        if ($isIntraState) {
            $halfRate = $gstRate / 2;
            $cgst = round($totalTax / 2, 2);
            $sgst = round($totalTax - $cgst, 2);

            return [
                'type' => 'intra_state',
                'rate' => $gstRate,
                'cgst_rate' => $halfRate,
                'cgst_amount' => $cgst,
                'sgst_rate' => $halfRate,
                'sgst_amount' => $sgst,
                'igst_rate' => 0,
                'igst_amount' => 0,
                'total_tax' => $totalTax,
                'grand_total' => round($amount + $totalTax, 2),
            ];
        }

        return [
            'type' => 'inter_state',
            'rate' => $gstRate,
            'cgst_rate' => 0,
            'cgst_amount' => 0,
            'sgst_rate' => 0,
            'sgst_amount' => 0,
            'igst_rate' => $gstRate,
            'igst_amount' => $totalTax,
            'total_tax' => $totalTax,
            'grand_total' => round($amount + $totalTax, 2),
        ];
    }
}
