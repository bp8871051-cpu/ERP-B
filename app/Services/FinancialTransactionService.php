<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FinancialTransactionService
{
    /**
     * Record a financial transaction ledger entry
     */
    public function recordTransaction(array $data): FinancialTransaction
    {
        $transactionNumber = $data['transaction_number'] ?? 'TXN-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return FinancialTransaction::create([
            'company_id' => $data['company_id'] ?? 1,
            'transaction_number' => $transactionNumber,
            'transaction_date' => $data['transaction_date'] ?? Carbon::today(),
            'transaction_type' => $data['transaction_type'], // sales_invoice, customer_payment, purchase, vendor_payment, expense, payroll, refund
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'account_type' => $data['account_type'] ?? 'bank',
            'account_id' => $data['account_id'] ?? null,
            'description' => $data['description'] ?? null,
            'debit' => $data['debit'] ?? 0,
            'credit' => $data['credit'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'posted',
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Record a balanced double-entry (Debit & Credit)
     */
    public function recordBalancedEntry(array $debitData, array $creditData): array
    {
        $batchId = 'TXN-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $debitData['transaction_number'] = $batchId . '-DR';
        $creditData['transaction_number'] = $batchId . '-CR';

        $debitTxn = $this->recordTransaction($debitData);
        $creditTxn = $this->recordTransaction($creditData);

        return [
            'debit' => $debitTxn,
            'credit' => $creditTxn,
        ];
    }
}
