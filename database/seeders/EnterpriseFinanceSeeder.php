<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\CashflowTransaction;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Tax;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnterpriseFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create(['name' => 'Falcon Technologies Inc.', 'email' => 'admin@falconerp.com']);
        $companyId = $company->id;
        $user = User::first();
        $userId = $user?->id ?? 1;

        // 1. 10 Accounts
        $accountConfigs = [
            ['name' => 'JPMorgan Chase Operating Account', 'bank' => 'JPMorgan Chase', 'number' => '1029384756', 'type' => 'bank', 'acc_type' => 'bank', 'balance' => 1250000.00, 'open' => 1000000.00, 'default' => true],
            ['name' => 'Silicon Valley Bank Payroll', 'bank' => 'Silicon Valley Bank', 'number' => '9876543210', 'type' => 'bank', 'acc_type' => 'bank', 'balance' => 450000.00, 'open' => 400000.00, 'default' => false],
            ['name' => 'HQ Petty Cash Safe', 'bank' => 'HQ Vault', 'number' => 'SAFE-01', 'type' => 'cash', 'acc_type' => 'cash', 'balance' => 35000.00, 'open' => 25000.00, 'default' => false],
            ['name' => 'American Express Corporate Platinum', 'bank' => 'American Express', 'number' => '3782822463', 'type' => 'credit_card', 'acc_type' => 'credit_card', 'balance' => 42000.00, 'open' => 10000.00, 'default' => false],
            ['name' => 'Stripe Merchant Gateway', 'bank' => 'Stripe Payments', 'number' => 'STRIPE-MERCH-01', 'type' => 'upi', 'acc_type' => 'upi', 'balance' => 184500.00, 'open' => 50000.00, 'default' => false],
            ['name' => 'HSBC International Settlement (EUR)', 'bank' => 'HSBC Global', 'number' => '4492019283', 'type' => 'bank', 'acc_type' => 'bank', 'balance' => 320000.00, 'open' => 250000.00, 'default' => false],
            ['name' => 'Tax Reserve Account', 'bank' => 'Bank of America', 'number' => '5523910294', 'type' => 'bank', 'acc_type' => 'bank', 'balance' => 195000.00, 'open' => 150000.00, 'default' => false],
            ['name' => 'Capital Equipment Reserve', 'bank' => 'Wells Fargo', 'number' => '6674829103', 'type' => 'bank', 'acc_type' => 'bank', 'balance' => 280000.00, 'open' => 250000.00, 'default' => false],
            ['name' => 'Retail POS Cash Register', 'bank' => 'Store POS Register 1', 'number' => 'POS-REG-01', 'type' => 'cash', 'acc_type' => 'cash', 'balance' => 18500.00, 'open' => 10000.00, 'default' => false],
            ['name' => 'UPI Merchant Business Wallet', 'bank' => 'Razorpay / HDFC', 'number' => 'UPI-WALLET-99', 'type' => 'upi', 'acc_type' => 'upi', 'balance' => 64200.00, 'open' => 30000.00, 'default' => false],
        ];

        $createdAccounts = [];
        foreach ($accountConfigs as $cfg) {
            $createdAccounts[] = Account::updateOrCreate(
                ['company_id' => $companyId, 'account_name' => $cfg['name']],
                [
                    'bank_name' => $cfg['bank'],
                    'account_number' => $cfg['number'],
                    'type' => $cfg['type'],
                    'account_type' => $cfg['acc_type'],
                    'balance' => $cfg['balance'],
                    'opening_balance' => $cfg['open'],
                    'currency' => 'USD',
                    'is_default' => $cfg['default'],
                    'status' => 'active',
                ]
            );
        }

        // 2. 20 Categories (Hierarchical parent/child)
        $parentCats = [
            'Office Expenses' => ['Stationery & Office Supplies', 'Printing & Xerox', 'Office Furniture', 'Kitchen & Pantry'],
            'Cloud & Technology' => ['AWS Infrastructure', 'SaaS Subscriptions', 'Hardware & IT Equipment', 'Internet & Fiber'],
            'Marketing & Sales' => ['Google & Meta Ads', 'Conferences & Expos', 'Content & SEO Services', 'Brand Merchandising'],
            'Personnel & Admin' => ['Staff Travel & Lodging', 'Professional Legal Fees', 'Accounting & Audit Fees', 'Staff Welfare & Training'],
            'Facilities & Utilities' => ['Headquarters Rent', 'Electricity & Water', 'Facility Maintenance', 'Office Security Services'],
        ];

        $allCategories = [];
        foreach ($parentCats as $parentName => $children) {
            $parent = ExpenseCategory::updateOrCreate(
                ['company_id' => $companyId, 'name' => $parentName],
                [
                    'code' => 'CAT-' . strtoupper(substr(str_replace(' ', '', $parentName), 0, 4)),
                    'description' => "Primary division for {$parentName}",
                    'budget' => 200000.00,
                    'status' => 'active',
                ]
            );
            $allCategories[] = $parent;

            foreach ($children as $idx => $childName) {
                $child = ExpenseCategory::updateOrCreate(
                    ['company_id' => $companyId, 'name' => $childName],
                    [
                        'parent_id' => $parent->id,
                        'code' => $parent->code . '-' . str_pad($idx + 1, 2, '0', STR_PAD_LEFT),
                        'description' => "Detailed sub-category for {$childName}",
                        'budget' => 50000.00,
                        'status' => 'active',
                    ]
                );
                $allCategories[] = $child;
            }
        }

        // 3. 10 Taxes
        $taxConfigs = [
            ['name' => 'GST 18%', 'code' => 'GST18', 'type' => 'percentage', 'rate' => 18.0000, 'compound' => false, 'desc' => 'Standard Goods & Services Tax'],
            ['name' => 'CGST 9%', 'code' => 'CGST9', 'type' => 'percentage', 'rate' => 9.0000, 'compound' => false, 'desc' => 'Central GST (Intra-state)'],
            ['name' => 'SGST 9%', 'code' => 'SGST9', 'type' => 'percentage', 'rate' => 9.0000, 'compound' => false, 'desc' => 'State GST (Intra-state)'],
            ['name' => 'IGST 18%', 'code' => 'IGST18', 'type' => 'percentage', 'rate' => 18.0000, 'compound' => false, 'desc' => 'Integrated GST (Inter-state)'],
            ['name' => 'VAT 5%', 'code' => 'VAT5', 'type' => 'percentage', 'rate' => 5.0000, 'compound' => false, 'desc' => 'Value Added Tax on Essentials'],
            ['name' => 'State Sales Tax 8.25%', 'code' => 'SALES_TAX_CA', 'type' => 'percentage', 'rate' => 8.2500, 'compound' => false, 'desc' => 'California State & County Sales Tax'],
            ['name' => 'Service Tax 15%', 'code' => 'SRV15', 'type' => 'percentage', 'rate' => 15.0000, 'compound' => false, 'desc' => 'Professional & IT Services Tax'],
            ['name' => 'Environmental / Eco Cess 2%', 'code' => 'ECO_CESS', 'type' => 'compound', 'rate' => 2.0000, 'compound' => true, 'desc' => 'Compound environmental levy'],
            ['name' => 'Luxury Goods Surcharge 12%', 'code' => 'LUX12', 'type' => 'percentage', 'rate' => 12.0000, 'compound' => false, 'desc' => 'Special equipment surcharge'],
            ['name' => 'Zero-Rated Export 0%', 'code' => 'ZERO_EXP', 'type' => 'percentage', 'rate' => 0.0000, 'compound' => false, 'desc' => 'Export goods and SEZ supplies'],
        ];

        foreach ($taxConfigs as $tc) {
            Tax::updateOrCreate(
                ['company_id' => $companyId, 'code' => $tc['code']],
                [
                    'name' => $tc['name'],
                    'type' => $tc['type'],
                    'rate' => $tc['rate'],
                    'is_compound' => $tc['compound'],
                    'description' => $tc['desc'],
                    'status' => 'active',
                ]
            );
        }

        // 4. 10 Budgets
        $departments = Department::where('company_id', $companyId)->get();
        $dept1 = $departments->first();
        $dept2 = $departments->skip(1)->first() ?? $dept1;

        $budgetConfigs = [
            ['name' => 'FY 2026-27 Engineering R&D Budget', 'year' => 'FY 2026-27', 'amount' => 450000.00, 'dept' => $dept1?->id, 'cat' => $allCategories[5]->id ?? null],
            ['name' => 'FY 2026-27 Enterprise Marketing Campaign', 'year' => 'FY 2026-27', 'amount' => 250000.00, 'dept' => $dept2?->id, 'cat' => $allCategories[10]->id ?? null],
            ['name' => 'Q1 Cloud & Server Infrastructure Allocation', 'year' => 'FY 2026-27', 'amount' => 180000.00, 'dept' => $dept1?->id, 'cat' => $allCategories[6]->id ?? null],
            ['name' => 'Annual Facilities & HQ Rent Allocation', 'year' => 'FY 2026-27', 'amount' => 220000.00, 'dept' => null, 'cat' => $allCategories[18]->id ?? null],
            ['name' => 'Global Sales Travel & Client Entertainment', 'year' => 'FY 2026-27', 'amount' => 95000.00, 'dept' => $dept2?->id, 'cat' => $allCategories[14]->id ?? null],
            ['name' => 'Office Supplies & Automation Equipment', 'year' => 'FY 2026-27', 'amount' => 60000.00, 'dept' => null, 'cat' => $allCategories[1]->id ?? null],
            ['name' => 'Talent Acquisition & Employee Development', 'year' => 'FY 2026-27', 'amount' => 120000.00, 'dept' => null, 'cat' => $allCategories[17]->id ?? null],
            ['name' => 'Corporate Legal & Governance Advisory', 'year' => 'FY 2026-27', 'amount' => 85000.00, 'dept' => null, 'cat' => $allCategories[15]->id ?? null],
            ['name' => 'Hardware Fleet Upgrade & Refresh', 'year' => 'FY 2026-27', 'amount' => 140000.00, 'dept' => $dept1?->id, 'cat' => $allCategories[7]->id ?? null],
            ['name' => 'Contingency & Emergency Operational Reserve', 'year' => 'FY 2026-27', 'amount' => 100000.00, 'dept' => null, 'cat' => null],
        ];

        foreach ($budgetConfigs as $idx => $bc) {
            $budget = Budget::updateOrCreate(
                ['company_id' => $companyId, 'name' => $bc['name']],
                [
                    'financial_year' => $bc['year'],
                    'start_date' => Carbon::parse('2026-04-01'),
                    'end_date' => Carbon::parse('2027-03-31'),
                    'department_id' => $bc['dept'],
                    'category_id' => $bc['cat'],
                    'budget_amount' => $bc['amount'],
                    'actual_spent' => round($bc['amount'] * (0.35 + ($idx * 0.05)), 2),
                    'notes' => 'Executive board approved operational expenditure allocation.',
                    'status' => 'approved',
                    'created_by' => $userId,
                ]
            );

            // Add budget items
            if ($budget->items()->count() == 0) {
                $budget->items()->createMany([
                    ['item_name' => 'Phase 1 Milestones', 'planned_amount' => $bc['amount'] * 0.40, 'actual_amount' => $bc['amount'] * 0.30],
                    ['item_name' => 'Phase 2 Milestones', 'planned_amount' => $bc['amount'] * 0.35, 'actual_amount' => $bc['amount'] * 0.15],
                    ['item_name' => 'Buffer & Ancillary Expenses', 'planned_amount' => $bc['amount'] * 0.25, 'actual_amount' => $bc['amount'] * 0.05],
                ]);
            }
        }

        // 5. 50 Expenses
        $vendors = Vendor::where('company_id', $companyId)->get();
        if ($vendors->isEmpty()) {
            $vendors = [
                Vendor::create(['company_id' => $companyId, 'name' => 'Amazon Web Services', 'email' => 'billing@aws.com', 'balance' => 24000]),
                Vendor::create(['company_id' => $companyId, 'name' => 'WeWork Real Estate LLC', 'email' => 'lease@wework.com', 'balance' => 38000]),
                Vendor::create(['company_id' => $companyId, 'name' => 'Google Cloud Billing', 'email' => 'gcp-billing@google.com', 'balance' => 12500]),
                Vendor::create(['company_id' => $companyId, 'name' => 'Dell Technologies Corp', 'email' => 'enterprise@dell.com', 'balance' => 45000]),
                Vendor::create(['company_id' => $companyId, 'name' => 'Staples Office Solutions', 'email' => 'supplies@staples.com', 'balance' => 4500]),
            ];
            $vendors = collect($vendors);
        }

        $paymentMethods = ['bank_transfer', 'card', 'cash', 'upi', 'cheque'];
        $statuses = ['paid', 'paid', 'paid', 'approved', 'submitted', 'draft'];

        $expenseDescriptions = [
            'Monthly AWS Production Cluster hosting & RDS storage',
            'HQ Office Suite 400 Lease settlement for current term',
            'Google Workspace Business Plus 150 User Licenses',
            'New Dell Latitude 5540 Developer laptops (Batch of 5)',
            'Ergonomic mesh chairs and standing desks for engineering',
            'Quarterly Google Search Ads campaign promotion',
            'LinkedIn Recruiter Professional Corporate Seats',
            'High-speed commercial 1Gbps dedicated fiber internet',
            'Annual statutory corporate financial audit & filing',
            'Executive travel flight tickets & conference lodging',
            'Staff cafeteria coffee beans, pantry snacks & beverages',
            'Cisco Meraki WiFi 6 Access Points & Managed Switch',
            'Adobe Creative Cloud Enterprise team subscriptions',
            'Monthly municipal electricity & air conditioning utility',
            'Commercial general liability insurance premium',
            'Quarterly team offsite hackathon dinner & venue hire',
            'GitHub Enterprise Cloud code repository licensing',
            'Zendesk Suite enterprise customer support portal',
            'Warehouse CCTV security monitoring & access badges',
            'Printer toner cartridges, paper reams and stationery',
        ];

        for ($i = 1; $i <= 50; $i++) {
            $desc = $expenseDescriptions[($i - 1) % count($expenseDescriptions)];
            $vendor = $vendors[($i - 1) % $vendors->count()];
            $cat = $allCategories[($i - 1) % count($allCategories)];
            $acc = $createdAccounts[($i - 1) % count($createdAccounts)];
            $amount = round(rand(800, 18500) + (rand(10, 99) / 100), 2);
            $tax = round($amount * 0.18, 2);
            $discount = round(rand(0, 100), 2);
            $total = round($amount + $tax - $discount, 2);
            $status = $statuses[($i - 1) % count($statuses)];
            $method = $paymentMethods[($i - 1) % count($paymentMethods)];
            $expDate = Carbon::today()->subDays($i * 2);

            $exp = Expense::updateOrCreate(
                ['company_id' => $companyId, 'expense_number' => 'EXP-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT)],
                [
                    'expense_category_id' => $cat->id,
                    'vendor_id' => $vendor->id,
                    'account_id' => $acc->id,
                    'category' => $cat->name,
                    'amount' => $amount,
                    'tax' => $tax,
                    'discount' => $discount,
                    'total' => $total,
                    'expense_date' => $expDate,
                    'payment_method' => $method,
                    'reference' => 'REF-' . strtoupper(Str::random(6)),
                    'description' => $desc,
                    'status' => $status,
                    'created_by' => $userId,
                    'approved_by' => in_array($status, ['approved', 'paid']) ? $userId : null,
                    'approved_at' => in_array($status, ['approved', 'paid']) ? $expDate : null,
                    'paid_at' => $status === 'paid' ? $expDate : null,
                ]
            );

            // If paid, create ledger entry
            if ($status === 'paid') {
                FinancialTransaction::firstOrCreate(
                    ['company_id' => $companyId, 'reference_type' => 'Expense', 'reference_id' => $exp->id],
                    [
                        'transaction_number' => 'TXN-EXP-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                        'transaction_date' => $expDate,
                        'transaction_type' => 'expense',
                        'account_type' => 'expense',
                        'account_id' => $acc->id,
                        'description' => "Expense: {$desc}",
                        'debit' => $total,
                        'credit' => 0,
                        'status' => 'posted',
                        'created_by' => $userId,
                    ]
                );
            }
        }

        // 6. 50 Payments
        $customers = Customer::where('company_id', $companyId)->get();
        if ($customers->isEmpty()) {
            $customers = [
                Customer::create(['company_id' => $companyId, 'name' => 'Acme Global Logistics', 'balance' => 48000]),
                Customer::create(['company_id' => $companyId, 'name' => 'Nexis Retail Chain', 'balance' => 32000]),
                Customer::create(['company_id' => $companyId, 'name' => 'Apex Healthcare Systems', 'balance' => 64000]),
            ];
            $customers = collect($customers);
        }

        $invoices = Invoice::where('company_id', $companyId)->get();
        $purchases = Purchase::where('company_id', $companyId)->get();

        for ($k = 1; $k <= 50; $k++) {
            $isCust = ($k % 2 == 1);
            $payType = $isCust ? 'customer_payment' : 'vendor_payment';
            $partyName = $isCust ? $customers[($k - 1) % $customers->count()]->name : $vendors[($k - 1) % $vendors->count()]->name;
            $partyId = $isCust ? $customers[($k - 1) % $customers->count()]->id : $vendors[($k - 1) % $vendors->count()]->id;
            $partyType = $isCust ? 'customer' : 'vendor';
            $acc = $createdAccounts[($k - 1) % count($createdAccounts)];
            $amount = round(rand(2500, 32000) + (rand(10, 99) / 100), 2);
            $payDate = Carbon::today()->subDays($k);
            $payNum = 'PAY-2026-' . str_pad($k, 4, '0', STR_PAD_LEFT);

            $payment = Payment::updateOrCreate(
                ['company_id' => $companyId, 'payment_number' => $payNum],
                [
                    'payment_date' => $payDate,
                    'payment_type' => $payType,
                    'party_type' => $partyType,
                    'party_id' => $partyId,
                    'party_name' => $partyName,
                    'invoice_id' => ($isCust && $invoices->isNotEmpty()) ? $invoices[($k - 1) % $invoices->count()]->id : null,
                    'purchase_id' => (!$isCust && $purchases->isNotEmpty()) ? $purchases[($k - 1) % $purchases->count()]->id : null,
                    'account_id' => $acc->id,
                    'payment_method' => $paymentMethods[($k - 1) % count($paymentMethods)],
                    'reference' => 'TRX-' . strtoupper(Str::random(7)),
                    'amount' => $amount,
                    'notes' => "Official ERP settlement record for {$partyName}",
                    'status' => 'completed',
                    'created_by' => $userId,
                ]
            );

            // Create cashflow transaction
            CashflowTransaction::firstOrCreate(
                ['company_id' => $companyId, 'reference' => $payment->payment_number],
                [
                    'account_id' => $acc->id,
                    'date' => $payDate,
                    'type' => $isCust ? 'inflow' : 'outflow',
                    'category' => $isCust ? 'Customer Receipts' : 'Vendor Settlements',
                    'amount' => $amount,
                    'balance_after' => $acc->balance,
                    'description' => "Payment Settlement: {$partyName}",
                    'sourceable_type' => Payment::class,
                    'sourceable_id' => $payment->id,
                ]
            );
        }
    }
}
