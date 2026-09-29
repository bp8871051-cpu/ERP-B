<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashflowTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    protected FinancialTransactionService $transactionService;

    public function __construct(FinancialTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * List payments with filters & pagination
     */
    public function listPayments(array $filters = [], ?int $companyId = null)
    {
        $query = Payment::with(['invoice', 'purchase', 'expense', 'account', 'creator']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('payment_number', 'like', "%{$s}%")
                    ->orWhere('party_name', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('payment_date', [$filters['start_date'], $filters['end_date']]);
        }

        $sortField = $filters['sort_by'] ?? 'payment_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 15);
        return $query->paginate($perPage);
    }

    /**
     * Record a Customer Payment (Inflow)
     */
    public function recordCustomerPayment(array $data, ?int $companyId = null): Payment
    {
        return DB::transaction(function () use ($data, $companyId) {
            $companyId = $companyId ?? ($data['company_id'] ?? 1);
            $amount = (float) $data['amount'];
            $paymentDate = $data['payment_date'] ?? Carbon::today();
            $paymentNumber = $data['payment_number'] ?? ('PAY-CUST-' . date('Ymd') . '-' . strtoupper(Str::random(5)));

            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::lockForUpdate()->find($data['customer_id']);
            }

            $invoice = null;
            if (!empty($data['invoice_id'])) {
                $invoice = Invoice::lockForUpdate()->find($data['invoice_id']);
                if ($invoice && !$customer) {
                    $customer = Customer::lockForUpdate()->find($invoice->customer_id);
                }
            }

            // 1. Update Invoice if linked
            if ($invoice) {
                $newPaid = round((float) $invoice->paid_amount + $amount, 2);
                $newDue = max(0, round((float) $invoice->grand_total - $newPaid, 2));
                $newStatus = $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partially_paid' : 'unpaid');

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'due_amount' => $newDue,
                    'payment_status' => $newStatus,
                    'status' => $newStatus,
                ]);
            }

            // 2. Reduce Customer Outstanding Balance
            if ($customer) {
                $customer->decrement('balance', $amount);
            }

            // 3. Increment Cash/Bank Account balance
            $account = null;
            if (!empty($data['account_id'])) {
                $account = Account::lockForUpdate()->find($data['account_id']);
                if ($account) {
                    $account->increment('balance', $amount);
                }
            }

            // 4. Create Payment record
            $payment = Payment::create([
                'company_id' => $companyId,
                'payment_number' => $paymentNumber,
                'payment_date' => $paymentDate,
                'payment_type' => 'customer_payment',
                'party_type' => 'customer',
                'party_id' => $customer?->id,
                'party_name' => $customer?->name ?? ($data['party_name'] ?? 'Customer'),
                'invoice_id' => $invoice?->id,
                'account_id' => $account?->id,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? ($invoice?->invoice_number),
                'amount' => $amount,
                'notes' => $data['notes'] ?? ('Receipt for Invoice ' . ($invoice?->invoice_number ?? '')),
                'status' => 'completed',
                'created_by' => auth()->id() ?? 1,
            ]);

            // 5. Create Cashflow Inflow entry
            if ($account) {
                CashflowTransaction::create([
                    'company_id' => $companyId,
                    'account_id' => $account->id,
                    'date' => $paymentDate,
                    'reference' => $payment->payment_number,
                    'type' => 'inflow',
                    'category' => 'Sales Revenue',
                    'amount' => $amount,
                    'balance_after' => $account->balance,
                    'description' => "Customer Payment received: " . ($customer?->name ?? 'Customer') . " (Invoice: " . ($invoice?->invoice_number ?? 'N/A') . ")",
                    'sourceable_type' => Payment::class,
                    'sourceable_id' => $payment->id,
                ]);
            }

            // 6. Record Ledger Entry: Debit Cash/Bank, Credit Accounts Receivable
            $this->transactionService->recordBalancedEntry(
                [
                    'company_id' => $companyId,
                    'transaction_type' => 'customer_payment',
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'account_type' => $account?->account_type ?? 'bank',
                    'account_id' => $account?->id,
                    'description' => "Payment received from " . ($customer?->name ?? 'Customer'),
                    'debit' => $amount,
                    'credit' => 0,
                    'transaction_date' => $paymentDate,
                ],
                [
                    'company_id' => $companyId,
                    'transaction_type' => 'customer_payment',
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'account_type' => 'receivable',
                    'account_id' => null,
                    'description' => "Accounts Receivable reduction for " . ($customer?->name ?? 'Customer'),
                    'debit' => 0,
                    'credit' => $amount,
                    'transaction_date' => $paymentDate,
                ]
            );

            return $payment->fresh(['invoice', 'account']);
        });
    }

    /**
     * Record a Vendor Payment (Outflow)
     */
    public function recordVendorPayment(array $data, ?int $companyId = null): Payment
    {
        return DB::transaction(function () use ($data, $companyId) {
            $companyId = $companyId ?? ($data['company_id'] ?? 1);
            $amount = (float) $data['amount'];
            $paymentDate = $data['payment_date'] ?? Carbon::today();
            $paymentNumber = $data['payment_number'] ?? ('PAY-VEND-' . date('Ymd') . '-' . strtoupper(Str::random(5)));

            $vendor = null;
            if (!empty($data['vendor_id'])) {
                $vendor = Vendor::lockForUpdate()->find($data['vendor_id']);
            }

            $purchase = null;
            if (!empty($data['purchase_id'])) {
                $purchase = Purchase::lockForUpdate()->find($data['purchase_id']);
                if ($purchase && !$vendor) {
                    $vendor = Vendor::lockForUpdate()->find($purchase->vendor_id);
                }
            }

            // 1. Update Purchase if linked
            if ($purchase) {
                $newPaid = round((float) $purchase->paid_amount + $amount, 2);
                $newDue = max(0, round((float) $purchase->grand_total - $newPaid, 2));
                $newStatus = $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partially_paid' : 'unpaid');

                $purchase->update([
                    'paid_amount' => $newPaid,
                    'due_amount' => $newDue,
                    'payment_status' => $newStatus,
                ]);
            }

            // 2. Reduce Vendor Payable Balance
            if ($vendor) {
                $vendor->decrement('balance', $amount);
            }

            // 3. Decrement Cash/Bank Account balance
            $account = null;
            if (!empty($data['account_id'])) {
                $account = Account::lockForUpdate()->find($data['account_id']);
                if ($account) {
                    $account->decrement('balance', $amount);
                }
            }

            // 4. Create Payment record
            $payment = Payment::create([
                'company_id' => $companyId,
                'payment_number' => $paymentNumber,
                'payment_date' => $paymentDate,
                'payment_type' => 'vendor_payment',
                'party_type' => 'vendor',
                'party_id' => $vendor?->id,
                'party_name' => $vendor?->name ?? ($data['party_name'] ?? 'Vendor'),
                'purchase_id' => $purchase?->id,
                'account_id' => $account?->id,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? ($purchase?->purchase_number),
                'amount' => $amount,
                'notes' => $data['notes'] ?? ('Payment for Purchase ' . ($purchase?->purchase_number ?? '')),
                'status' => 'completed',
                'created_by' => auth()->id() ?? 1,
            ]);

            // 5. Create Cashflow Outflow entry
            if ($account) {
                CashflowTransaction::create([
                    'company_id' => $companyId,
                    'account_id' => $account->id,
                    'date' => $paymentDate,
                    'reference' => $payment->payment_number,
                    'type' => 'outflow',
                    'category' => 'Cost of Goods Sold',
                    'amount' => $amount,
                    'balance_after' => $account->balance,
                    'description' => "Vendor Payment to: " . ($vendor?->name ?? 'Vendor') . " (Purchase: " . ($purchase?->purchase_number ?? 'N/A') . ")",
                    'sourceable_type' => Payment::class,
                    'sourceable_id' => $payment->id,
                ]);
            }

            // 6. Record Ledger Entry: Debit Accounts Payable, Credit Cash/Bank
            $this->transactionService->recordBalancedEntry(
                [
                    'company_id' => $companyId,
                    'transaction_type' => 'vendor_payment',
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'account_type' => 'payable',
                    'account_id' => null,
                    'description' => "Accounts Payable settlement for " . ($vendor?->name ?? 'Vendor'),
                    'debit' => $amount,
                    'credit' => 0,
                    'transaction_date' => $paymentDate,
                ],
                [
                    'company_id' => $companyId,
                    'transaction_type' => 'vendor_payment',
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'account_type' => $account?->account_type ?? 'bank',
                    'account_id' => $account?->id,
                    'description' => "Vendor Payment from " . ($account?->account_name ?? 'Account'),
                    'debit' => 0,
                    'credit' => $amount,
                    'transaction_date' => $paymentDate,
                ]
            );

            return $payment->fresh(['purchase', 'account']);
        });
    }

    /**
     * Record a generic payment (Refund, Other Income, Other Payment)
     */
    public function recordGenericPayment(array $data, ?int $companyId = null): Payment
    {
        return DB::transaction(function () use ($data, $companyId) {
            $companyId = $companyId ?? ($data['company_id'] ?? 1);
            $amount = (float) $data['amount'];
            $type = $data['payment_type'] ?? 'other_payment';
            $isInflow = in_array($type, ['customer_payment', 'other_income']);

            $account = null;
            if (!empty($data['account_id'])) {
                $account = Account::lockForUpdate()->find($data['account_id']);
                if ($account) {
                    if ($isInflow) {
                        $account->increment('balance', $amount);
                    } else {
                        $account->decrement('balance', $amount);
                    }
                }
            }

            $payment = Payment::create([
                'company_id' => $companyId,
                'payment_number' => 'PAY-' . strtoupper(substr($type, 0, 3)) . '-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'payment_date' => $data['payment_date'] ?? Carbon::today(),
                'payment_type' => $type,
                'party_type' => $data['party_type'] ?? 'other',
                'party_id' => $data['party_id'] ?? null,
                'party_name' => $data['party_name'] ?? 'General Party',
                'account_id' => $account?->id,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? null,
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                'created_by' => auth()->id() ?? 1,
            ]);

            if ($account) {
                CashflowTransaction::create([
                    'company_id' => $companyId,
                    'account_id' => $account->id,
                    'date' => $payment->payment_date,
                    'reference' => $payment->payment_number,
                    'type' => $isInflow ? 'inflow' : 'outflow',
                    'category' => $isInflow ? 'Other Income' : 'Other Expenses',
                    'amount' => $amount,
                    'balance_after' => $account->balance,
                    'description' => $payment->notes ?? "Transaction: {$payment->payment_number}",
                    'sourceable_type' => Payment::class,
                    'sourceable_id' => $payment->id,
                ]);
            }

            return $payment;
        });
    }
}
