<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashflowTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseService
{
    protected FinancialTransactionService $transactionService;
    protected TaxService $taxService;

    public function __construct(
        FinancialTransactionService $transactionService,
        TaxService $taxService
    ) {
        $this->transactionService = $transactionService;
        $this->taxService = $taxService;
    }

    /**
     * Get paginated expenses with filters
     */
    public function listExpenses(array $filters = [], ?int $companyId = null)
    {
        $query = Expense::with(['categoryItem', 'vendor', 'account', 'creator', 'approver', 'attachments']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('expense_number', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$s}%"));
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('expense_category_id', $filters['category_id']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('expense_date', [$filters['start_date'], $filters['end_date']]);
        }

        $sortField = $filters['sort_by'] ?? 'expense_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 15);
        return $query->paginate($perPage);
    }

    /**
     * Create an expense
     */
    public function createExpense(array $data, ?int $companyId = null): Expense
    {
        $companyId = $companyId ?? ($data['company_id'] ?? 1);

        $amount = (float) ($data['amount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $total = round(($amount + $tax - $discount), 2);

        $expenseNumber = $data['expense_number'] ?? ('EXP-' . date('Ymd') . '-' . strtoupper(Str::random(5)));

        // Resolve category name if category_id provided
        $categoryName = $data['category'] ?? 'Miscellaneous';
        if (!empty($data['expense_category_id'])) {
            $cat = ExpenseCategory::find($data['expense_category_id']);
            if ($cat) $categoryName = $cat->name;
        }

        return Expense::create([
            'company_id' => $companyId,
            'expense_number' => $expenseNumber,
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'category' => $categoryName,
            'amount' => $amount,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'expense_date' => $data['expense_date'] ?? Carbon::today(),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'attachment' => $data['attachment'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'submitted', // draft, submitted, approved, paid, rejected
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Update an expense
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $expense->amount;
        $tax = isset($data['tax']) ? (float) $data['tax'] : (float) $expense->tax;
        $discount = isset($data['discount']) ? (float) $data['discount'] : (float) $expense->discount;
        $data['total'] = round(($amount + $tax - $discount), 2);

        if (!empty($data['expense_category_id'])) {
            $cat = ExpenseCategory::find($data['expense_category_id']);
            if ($cat) $data['category'] = $cat->name;
        }

        $expense->update($data);
        return $expense->fresh(['categoryItem', 'vendor', 'account']);
    }

    /**
     * Approve an expense
     */
    public function approveExpense(Expense $expense, ?int $userId = null): Expense
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => Carbon::now(),
        ]);

        return $expense;
    }

    /**
     * Reject an expense
     */
    public function rejectExpense(Expense $expense, ?string $reason = null): Expense
    {
        $notes = $expense->notes;
        if ($reason) {
            $notes = ($notes ? $notes . "\n" : '') . "Rejection reason: {$reason}";
        }

        $expense->update([
            'status' => 'rejected',
            'notes' => $notes,
        ]);

        return $expense;
    }

    /**
     * Process payment for an expense (Transactional)
     */
    public function payExpense(Expense $expense, array $paymentData = []): Expense
    {
        return DB::transaction(function () use ($expense, $paymentData) {
            $accountId = $paymentData['account_id'] ?? $expense->account_id;
            $paymentMethod = $paymentData['payment_method'] ?? $expense->payment_method;
            $paidDate = $paymentData['payment_date'] ?? Carbon::today();
            $amount = (float) ($expense->total > 0 ? $expense->total : $expense->amount);

            // 1. Deduct balance from Account if account specified
            $account = null;
            if ($accountId) {
                $account = Account::lockForUpdate()->find($accountId);
                if ($account) {
                    $account->decrement('balance', $amount);
                }
            }

            // 2. Create Payment record
            $payment = Payment::create([
                'company_id' => $expense->company_id,
                'payment_number' => 'PAY-EXP-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'payment_date' => $paidDate,
                'payment_type' => 'expense_payment',
                'party_type' => 'vendor',
                'party_id' => $expense->vendor_id,
                'party_name' => $expense->vendor?->name ?? 'Expense Payee',
                'expense_id' => $expense->id,
                'account_id' => $accountId,
                'payment_method' => $paymentMethod,
                'reference' => $expense->expense_number,
                'amount' => $amount,
                'notes' => 'Payment for Expense ' . $expense->expense_number,
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            // 3. Create Cashflow Outflow entry
            if ($account) {
                CashflowTransaction::create([
                    'company_id' => $expense->company_id,
                    'account_id' => $account->id,
                    'date' => $paidDate,
                    'reference' => $payment->payment_number,
                    'type' => 'outflow',
                    'category' => $expense->category,
                    'amount' => $amount,
                    'balance_after' => $account->balance,
                    'description' => "Expense Payment: {$expense->expense_number} - {$expense->description}",
                    'sourceable_type' => Expense::class,
                    'sourceable_id' => $expense->id,
                ]);
            }

            // 4. Create General Ledger Financial Transaction (Debit Expense, Credit Bank/Cash)
            $this->transactionService->recordBalancedEntry(
                [
                    'company_id' => $expense->company_id,
                    'transaction_type' => 'expense',
                    'reference_type' => 'Expense',
                    'reference_id' => $expense->id,
                    'account_type' => 'expense',
                    'account_id' => null,
                    'description' => "Expense: {$expense->category} ({$expense->expense_number})",
                    'debit' => $amount,
                    'credit' => 0,
                    'transaction_date' => $paidDate,
                ],
                [
                    'company_id' => $expense->company_id,
                    'transaction_type' => 'expense',
                    'reference_type' => 'Expense',
                    'reference_id' => $expense->id,
                    'account_type' => $account?->account_type ?? 'bank',
                    'account_id' => $account?->id,
                    'description' => "Payment for Expense {$expense->expense_number}",
                    'debit' => 0,
                    'credit' => $amount,
                    'transaction_date' => $paidDate,
                ]
            );

            // 5. Update Expense status to Paid
            $expense->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'account_id' => $accountId,
                'payment_method' => $paymentMethod,
            ]);

            return $expense->fresh(['categoryItem', 'vendor', 'account', 'attachments']);
        });
    }
}
