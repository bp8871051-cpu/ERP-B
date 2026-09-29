<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Tax;
use App\Services\BudgetService;
use App\Services\CashflowService;
use App\Services\ExpenseService;
use App\Services\FinanceReportService;
use App\Services\FinanceService;
use App\Services\PaymentService;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    protected FinanceService $financeService;
    protected ExpenseService $expenseService;
    protected PaymentService $paymentService;
    protected CashflowService $cashflowService;
    protected BudgetService $budgetService;
    protected TaxService $taxService;
    protected FinanceReportService $reportService;

    public function __construct(
        FinanceService $financeService,
        ExpenseService $expenseService,
        PaymentService $paymentService,
        CashflowService $cashflowService,
        BudgetService $budgetService,
        TaxService $taxService,
        FinanceReportService $reportService
    ) {
        $this->financeService = $financeService;
        $this->expenseService = $expenseService;
        $this->paymentService = $paymentService;
        $this->cashflowService = $cashflowService;
        $this->budgetService = $budgetService;
        $this->taxService = $taxService;
        $this->reportService = $reportService;
    }

    protected function getCompanyId(Request $request): ?int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    /**
     * Dashboard Overview
     */
    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->financeService->getDashboardData($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // ==========================================
    // EXPENSES & CATEGORIES
    // ==========================================

    public function expenses(Request $request): JsonResponse
    {
        $expenses = $this->expenseService->listExpenses($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $expenses]);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'account_id' => 'nullable|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:draft,submitted,approved,paid',
        ]);

        $expense = $this->expenseService->createExpense($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'message' => 'Expense created successfully', 'data' => $expense], 201);
    }

    public function showExpense($id): JsonResponse
    {
        $expense = Expense::with(['categoryItem', 'vendor', 'account', 'creator', 'approver', 'attachments'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $expense]);
    }

    public function updateExpense(Request $request, $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $updated = $this->expenseService->updateExpense($expense, $request->all());
        return response()->json(['status' => 'success', 'message' => 'Expense updated successfully', 'data' => $updated]);
    }

    public function destroyExpense($id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();
        return response()->json(['status' => 'success', 'message' => 'Expense deleted successfully']);
    }

    public function approveExpense(Request $request, $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $approved = $this->expenseService->approveExpense($expense, auth()->id());
        return response()->json(['status' => 'success', 'message' => 'Expense approved successfully', 'data' => $approved]);
    }

    public function payExpense(Request $request, $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $paid = $this->expenseService->payExpense($expense, $request->all());
        return response()->json(['status' => 'success', 'message' => 'Expense marked as paid and financial ledger updated', 'data' => $paid]);
    }

    public function expenseCategories(Request $request): JsonResponse
    {
        $categories = ExpenseCategory::with(['parent', 'children'])
            ->when($this->getCompanyId($request), fn($q, $id) => $q->where('company_id', $id))
            ->get();
        return response()->json(['status' => 'success', 'data' => $categories]);
    }

    public function storeExpenseCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'nullable|string',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $validated['company_id'] = $this->getCompanyId($request);
        $category = ExpenseCategory::create($validated);
        return response()->json(['status' => 'success', 'message' => 'Category created successfully', 'data' => $category], 201);
    }

    // ==========================================
    // PAYMENTS
    // ==========================================

    public function payments(Request $request): JsonResponse
    {
        $payments = $this->paymentService->listPayments($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $payments]);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_type' => 'required|string|in:customer_payment,vendor_payment,expense_payment,refund,other_income,other_payment',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|exists:accounts,id',
            'customer_id' => 'nullable|exists:customers,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'party_name' => 'nullable|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);

        if ($validated['payment_type'] === 'customer_payment') {
            $payment = $this->paymentService->recordCustomerPayment($validated, $companyId);
        } elseif ($validated['payment_type'] === 'vendor_payment') {
            $payment = $this->paymentService->recordVendorPayment($validated, $companyId);
        } else {
            $payment = $this->paymentService->recordGenericPayment($validated, $companyId);
        }

        return response()->json(['status' => 'success', 'message' => 'Payment recorded and ledger updated', 'data' => $payment], 201);
    }

    // ==========================================
    // CASHFLOW & ACCOUNTS
    // ==========================================

    public function cashflow(Request $request): JsonResponse
    {
        $overview = $this->cashflowService->getCashflowOverview($this->getCompanyId($request), $request->get('period', 'monthly'));
        $transactions = $this->cashflowService->getTransactions($request->all(), $this->getCompanyId($request));

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => $overview,
                'transactions' => $transactions,
            ]
        ]);
    }

    public function accounts(Request $request): JsonResponse
    {
        $accounts = Account::when($this->getCompanyId($request), fn($q, $id) => $q->where('company_id', $id))->get();
        return response()->json(['status' => 'success', 'data' => $accounts]);
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => 'required|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'type' => 'nullable|string',
            'account_type' => 'required|string|in:cash,bank,upi,credit_card,other',
            'opening_balance' => 'nullable|numeric',
            'currency' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getCompanyId($request);
        $validated['balance'] = $validated['opening_balance'] ?? 0;
        $account = Account::create($validated);

        return response()->json(['status' => 'success', 'message' => 'Account created successfully', 'data' => $account], 201);
    }

    // ==========================================
    // BUDGETING
    // ==========================================

    public function budgets(Request $request): JsonResponse
    {
        $budgets = $this->budgetService->listBudgets($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $budgets]);
    }

    public function storeBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'financial_year' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:expense_categories,id',
            'budget_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $budget = $this->budgetService->createBudget($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'message' => 'Budget created successfully', 'data' => $budget], 201);
    }

    // ==========================================
    // TAXES
    // ==========================================

    public function taxes(Request $request): JsonResponse
    {
        $taxes = Tax::when($this->getCompanyId($request), fn($q, $id) => $q->where('company_id', $id))->get();
        return response()->json(['status' => 'success', 'data' => $taxes]);
    }

    public function storeTax(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:taxes,code',
            'type' => 'required|string|in:percentage,fixed,compound',
            'rate' => 'required|numeric|min:0',
            'is_compound' => 'nullable|boolean',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $validated['company_id'] = $this->getCompanyId($request);
        $tax = Tax::create($validated);
        return response()->json(['status' => 'success', 'message' => 'Tax rule created successfully', 'data' => $tax], 201);
    }

    // ==========================================
    // REPORTS
    // ==========================================

    public function reports(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $type = $request->get('type', 'pnl'); // pnl, balance, outstanding

        if ($type === 'pnl') {
            $data = $this->reportService->getProfitAndLoss($companyId, $request->all());
        } elseif ($type === 'balance') {
            $data = $this->reportService->getBalanceSummary($companyId);
        } else {
            $data = $this->reportService->getOutstandingSummary($companyId);
        }

        return response()->json(['status' => 'success', 'data' => $data]);
    }
}
