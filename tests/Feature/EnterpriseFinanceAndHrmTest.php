<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\Account;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseFinanceAndHrmTest extends TestCase
{
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::first() ?? Company::create([
            'name' => 'Falcon ERP Testing Corp',
            'code' => 'FALCON-TEST',
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $this->user = User::first() ?? User::create([
            'company_id' => $this->company->id,
            'name' => 'Test Super Admin',
            'email' => 'testadmin@falconerp.com',
            'password' => bcrypt('password'),
            'role' => 'Super Admin',
            'is_active' => true,
        ]);
    }

    public function test_finance_dashboard_returns_real_metrics()
    {
        $response = $this->actingAs($this->user)->getJson('/api/finance/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'metrics' => [
                    'totalRevenue',
                    'totalExpenses',
                    'netProfit',
                    'accountsReceivable',
                    'accountsPayable',
                    'cashBalance',
                    'bankBalance',
                ],
                'charts',
                'recentTransactions',
            ],
        ]);
    }

    public function test_expense_approval_and_payment_updates_ledger_and_balance()
    {
        $category = ExpenseCategory::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Test Hosting & Cloud'],
            ['code' => 'TEST-CLOUD', 'is_active' => true]
        );

        $account = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'account_name' => 'Test Vault Account'],
            ['account_type' => 'bank', 'opening_balance' => 500000, 'balance' => 500000, 'currency' => 'INR']
        );
        $account->update(['balance' => 500000]);

        $initialBalance = 500000.00;

        // 1. Create Expense
        $expense = Expense::create([
            'company_id' => $this->company->id,
            'expense_number' => 'EXP-TEST-' . rand(1000, 9999),
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $category->id,
            'description' => 'Dedicated cloud server subscription',
            'amount' => 10000,
            'tax' => 1800,
            'total' => 11800,
            'status' => 'submitted',
            'created_by' => $this->user->id,
        ]);

        // 2. Approve Expense
        $approveRes = $this->actingAs($this->user)->postJson("/api/finance/expenses/{$expense->id}/approve");
        $approveRes->assertStatus(200);
        $this->assertEquals('approved', $expense->fresh()->status);

        // 3. Pay Expense
        $payRes = $this->actingAs($this->user)->postJson("/api/finance/expenses/{$expense->id}/pay", [
            'account_id' => $account->id,
            'notes' => 'Disbursed via automated test',
        ]);
        $payRes->assertStatus(200);

        // Verify status is paid
        $this->assertEquals('paid', $expense->fresh()->status);

        // Verify Account balance was deducted
        $newBalance = (float) $account->fresh()->balance;
        $this->assertEquals($initialBalance - 11800, $newBalance);

        // Verify FinancialTransaction ledger entry exists
        $this->assertDatabaseHas('financial_transactions', [
            'company_id' => $this->company->id,
            'reference_type' => 'Expense',
            'reference_id' => $expense->id,
            'debit' => 11800,
        ]);
    }

    public function test_hrm_dashboard_returns_real_metrics()
    {
        $response = $this->actingAs($this->user)->getJson('/api/hrm/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'metrics' => [
                    'totalEmployees',
                    'activeEmployees',
                    'onLeaveToday',
                    'presentToday',
                    'openPositions',
                    'monthlyPayroll',
                ],
                'charts' => [
                    'departments',
                    'attendanceTrend',
                    'payrollTrend',
                    'recruitmentPipeline',
                    'employeeStatus',
                ],
                'recentLeaveRequests',
                'recentEmployees',
            ],
        ]);
    }

    public function test_attendance_check_in_and_check_out()
    {
        $dept = Department::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Testing Dept'],
            ['code' => 'TEST-DEPT', 'status' => 'active']
        );
        $des = Designation::firstOrCreate(
            ['title' => 'Quality Engineer'],
            ['department_id' => $dept->id, 'code' => 'QE-TEST', 'level' => 'Mid', 'status' => 'active']
        );

        $emp = Employee::firstOrCreate(
            ['company_id' => $this->company->id, 'email' => 'test.qa@falconerp.com'],
            [
                'employee_code' => 'EMP-TEST-01',
                'first_name' => 'Tester',
                'last_name' => 'QA',
                'department_id' => $dept->id,
                'designation_id' => $des->id,
                'joining_date' => now()->toDateString(),
                'status' => 'active',
            ]
        );

        $date = now()->addDays(rand(10, 50))->toDateString(); // future unique day

        $markRes = $this->actingAs($this->user)->postJson('/api/hrm/attendance', [
            'employee_id' => $emp->id,
            'date' => $date,
            'check_in' => '09:15:00',
            'check_out' => '18:00:00',
            'status' => 'present',
        ]);

        $markRes->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $emp->id,
            'date' => $date,
            'status' => 'present',
        ]);
    }

    public function test_payroll_disbursement_integrates_with_finance()
    {
        $account = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'account_name' => 'Corporate Payroll Vault'],
            ['account_type' => 'bank', 'opening_balance' => 1000000, 'balance' => 1000000, 'currency' => 'INR']
        );

        $period = PayrollPeriod::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Test Batch ' . rand(100, 999)],
            ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString(), 'status' => 'active']
        );

        $emp = Employee::first() ?? Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP-TEST-PAY',
            'first_name' => 'Payroll',
            'last_name' => 'Recipient',
            'email' => 'payroll.recipient@falconerp.com',
            'status' => 'active',
        ]);

        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'employee_id' => $emp->id,
            'payroll_period_id' => $period->id,
            'month' => now()->format('F'),
            'year' => now()->year,
            'basic_salary' => 50000,
            'allowances' => 10000,
            'gross_salary' => 60000,
            'deductions' => 5000,
            'net_salary' => 55000,
            'status' => 'approved',
        ]);

        // Disburse Payroll via API
        $payRes = $this->actingAs($this->user)->postJson("/api/hrm/payroll/{$payroll->id}/pay", [
            'account_id' => $account->id,
        ]);

        $payRes->assertStatus(200);

        // Verify status is paid
        $this->assertEquals('paid', $payroll->fresh()->status);

        // Verify Expense record was created in Finance
        $this->assertDatabaseHas('expenses', [
            'company_id' => $this->company->id,
            'total' => 55000,
            'status' => 'paid',
        ]);
    }
}
