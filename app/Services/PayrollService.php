<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollService
{
    protected FinancialTransactionService $transactionService;

    public function __construct(FinancialTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Calculate monthly payroll for an employee
     */
    public function calculateEmployeePayroll(Employee $employee, PayrollPeriod $period): Payroll
    {
        $startDate = $period->start_date;
        $endDate = $period->end_date;
        $totalWorkingDays = $startDate->diffInDays($endDate) + 1;

        // Fetch salary structure or fallback to base salary
        $structure = $employee->salaryStructure;
        $basicSalary = $structure ? (float) $structure->basic_salary : ((float) $employee->salary * 0.50);
        $hra = $structure ? (float) $structure->hra : ((float) $employee->salary * 0.20);
        $transport = $structure ? (float) $structure->transport_allowance : 2000.00;
        $medical = $structure ? (float) $structure->medical_allowance : 1500.00;
        $special = $structure ? (float) $structure->special_allowance : 2500.00;
        $otherAllowances = $structure ? (float) $structure->other_allowances : 0;

        $totalAllowances = $hra + $transport + $medical + $special + $otherAllowances;

        $pf = $structure ? (float) $structure->pf : round($basicSalary * 0.12, 2);
        $esi = $structure ? (float) $structure->esi : round($basicSalary * 0.0075, 2);
        $profTax = $structure ? (float) $structure->professional_tax : 200.00;
        $tds = $structure ? (float) $structure->tds : round($basicSalary * 0.10, 2);
        $otherDeductions = $structure ? (float) $structure->other_deductions : 0;

        $totalDeductions = $pf + $esi + $profTax + $tds + $otherDeductions;

        // Fetch actual attendance
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $presentDays = $attendances->whereIn('status', ['present', 'remote', 'late'])->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $paidLeaves = $attendances->where('status', 'leave')->count();
        $unpaidLeaves = 0;

        // If no attendance marked yet, default to full month attendance
        if ($attendances->isEmpty()) {
            $presentDays = $totalWorkingDays - 4; // Minus weekends
        }

        $overtimeMinutes = $attendances->sum('overtime_minutes');
        $overtimeHours = round($overtimeMinutes / 60, 2);
        $hourlyRate = $basicSalary / max(1, ($totalWorkingDays * 8));
        $overtimeAmount = round($overtimeHours * $hourlyRate * 1.5, 2);

        $grossSalary = round($basicSalary + $totalAllowances + $overtimeAmount, 2);
        $netSalary = round(max(0, $grossSalary - $totalDeductions), 2);

        return DB::transaction(function () use (
            $employee, $period, $basicSalary, $totalAllowances, $grossSalary,
            $totalDeductions, $overtimeAmount, $netSalary, $totalWorkingDays,
            $presentDays, $absentDays, $paidLeaves, $unpaidLeaves, $overtimeHours,
            $hra, $transport, $medical, $special, $pf, $esi, $profTax, $tds
        ) {
            $payroll = Payroll::updateOrCreate(
                [
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'payroll_period_id' => $period->id,
                ],
                [
                    'month' => $period->start_date->format('F'),
                    'year' => (int) $period->start_date->format('Y'),
                    'basic_salary' => $basicSalary,
                    'allowances' => $totalAllowances,
                    'gross_salary' => $grossSalary,
                    'deductions' => $totalDeductions,
                    'overtime_amount' => $overtimeAmount,
                    'net_salary' => $netSalary,
                    'working_days' => $totalWorkingDays,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'paid_leaves' => $paidLeaves,
                    'unpaid_leaves' => $unpaidLeaves,
                    'overtime_hours' => $overtimeHours,
                    'status' => 'calculated',
                    'payment_date' => $period->payment_date,
                ]
            );

            // Rebuild item breakdown
            $payroll->items()->delete();

            $items = [
                ['type' => 'allowance', 'name' => 'House Rent Allowance (HRA)', 'amount' => $hra],
                ['type' => 'allowance', 'name' => 'Transport Allowance', 'amount' => $transport],
                ['type' => 'allowance', 'name' => 'Medical Allowance', 'amount' => $medical],
                ['type' => 'allowance', 'name' => 'Special Allowance', 'amount' => $special],
                ['type' => 'deduction', 'name' => 'Provident Fund (PF)', 'amount' => $pf],
                ['type' => 'deduction', 'name' => 'Employee State Insurance (ESI)', 'amount' => $esi],
                ['type' => 'deduction', 'name' => 'Professional Tax', 'amount' => $profTax],
                ['type' => 'deduction', 'name' => 'Tax Deducted at Source (TDS)', 'amount' => $tds],
            ];

            if ($overtimeAmount > 0) {
                $items[] = ['type' => 'allowance', 'name' => "Overtime ({$overtimeHours} hrs)", 'amount' => $overtimeAmount];
            }

            foreach ($items as $item) {
                $payroll->items()->create($item);
            }

            return $payroll->fresh(['employee.department', 'employee.designation', 'items']);
        });
    }

    /**
     * Process & Pay Payroll (Integrates with Finance Module!)
     */
    public function payPayroll(Payroll $payroll, ?int $accountId = null): Payroll
    {
        return DB::transaction(function () use ($payroll, $accountId) {
            $companyId = $payroll->company_id;
            $netAmount = (float) $payroll->net_salary;
            $paidDate = Carbon::today();

            // 1. Find Bank Account
            $account = null;
            if ($accountId) {
                $account = Account::lockForUpdate()->find($accountId);
            } else {
                $account = Account::lockForUpdate()->where('company_id', $companyId)->first();
            }

            if ($account) {
                $account->decrement('balance', $netAmount);
            }

            // 2. Create Salary Expense in Finance module
            $salaryCategory = ExpenseCategory::firstOrCreate(
                ['company_id' => $companyId, 'name' => 'Salary'],
                ['code' => 'EXP-SAL', 'description' => 'Employee Salaries & Payroll', 'status' => 'active']
            );

            $expense = Expense::create([
                'company_id' => $companyId,
                'expense_number' => 'EXP-PAY-' . date('Ymd') . '-' . $payroll->id,
                'expense_category_id' => $salaryCategory->id,
                'account_id' => $account?->id,
                'category' => 'Salary',
                'amount' => $payroll->gross_salary,
                'tax' => 0,
                'discount' => $payroll->deductions,
                'total' => $netAmount,
                'expense_date' => $paidDate,
                'payment_method' => 'bank_transfer',
                'reference' => 'PAYROLL-' . $payroll->month . '-' . $payroll->year,
                'description' => "Salary payment for {$payroll->employee->full_name} ({$payroll->employee->employee_code})",
                'status' => 'paid',
                'created_by' => auth()->id() ?? 1,
                'approved_by' => auth()->id() ?? 1,
                'approved_at' => Carbon::now(),
                'paid_at' => Carbon::now(),
            ]);

            // 3. Create Payment record in Finance
            Payment::create([
                'company_id' => $companyId,
                'payment_number' => 'PAY-SLR-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'payment_date' => $paidDate,
                'payment_type' => 'expense_payment',
                'party_type' => 'employee',
                'party_id' => $payroll->employee_id,
                'party_name' => $payroll->employee->full_name,
                'expense_id' => $expense->id,
                'account_id' => $account?->id,
                'payment_method' => 'bank_transfer',
                'reference' => $payroll->month . ' ' . $payroll->year . ' Salary',
                'amount' => $netAmount,
                'notes' => "Direct salary disbursement to account ending " . substr($payroll->employee->account_number ?? '1234', -4),
                'status' => 'completed',
                'created_by' => auth()->id() ?? 1,
            ]);

            // 4. Record General Ledger Transaction: Debit Salary Expense, Credit Bank
            $this->transactionService->recordBalancedEntry(
                [
                    'company_id' => $companyId,
                    'transaction_type' => 'payroll',
                    'reference_type' => 'Payroll',
                    'reference_id' => $payroll->id,
                    'account_type' => 'expense',
                    'account_id' => null,
                    'description' => "Salary Expense: {$payroll->employee->full_name} ({$payroll->month} {$payroll->year})",
                    'debit' => $netAmount,
                    'credit' => 0,
                    'transaction_date' => $paidDate,
                ],
                [
                    'company_id' => $companyId,
                    'transaction_type' => 'payroll',
                    'reference_type' => 'Payroll',
                    'reference_id' => $payroll->id,
                    'account_type' => 'bank',
                    'account_id' => $account?->id,
                    'description' => "Salary payout from {$account?->account_name}",
                    'debit' => 0,
                    'credit' => $netAmount,
                    'transaction_date' => $paidDate,
                ]
            );

            // 5. Generate Payslip record
            $existingPayslip = Payslip::where('payroll_id', $payroll->id)->first();
            $payslipNumber = $existingPayslip ? $existingPayslip->payslip_number : ('PS-' . $payroll->year . '-' . strtoupper(substr($payroll->month, 0, 3)) . '-' . str_pad($payroll->id, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)));

            Payslip::updateOrCreate(
                ['payroll_id' => $payroll->id],
                [
                    'payslip_number' => $payslipNumber,
                    'generated_at' => Carbon::now(),
                ]
            );

            // 6. Update Payroll status to Paid
            $payroll->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'processed_at' => $payroll->processed_at ?? Carbon::now(),
            ]);

            return $payroll->fresh(['employee', 'items', 'payslip']);
        });
    }
}
