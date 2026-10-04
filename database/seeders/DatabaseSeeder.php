<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobPosition;
use App\Models\Lead;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Milestone;
use App\Models\Opportunity;
use App\Models\Payroll;
use App\Models\Permission;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\Timesheet;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Companies
        $falcon = Company::create([
            'name' => 'Falcon LLP',
            'code' => 'FALCON-01',
            'email' => 'contact@falconllp.com',
            'phone' => '+1 (555) 019-2834',
            'address' => '742 Evergreen Terrace, Suite 400, San Francisco, CA 94107',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'tax_number' => 'US-982348123',
            'status' => 'active',
        ]);

        $apex = Company::create([
            'name' => 'Apex Global Technologies',
            'code' => 'APEX-02',
            'email' => 'operations@apextech.io',
            'phone' => '+1 (555) 019-4821',
            'address' => '100 Innovation Way, Austin, TX 78701',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'tax_number' => 'US-773821094',
            'status' => 'active',
        ]);

        // 2. Departments
        $deptEngineering = Department::create(['company_id' => $falcon->id, 'name' => 'Engineering', 'code' => 'ENG', 'description' => 'Software & Hardware R&D']);
        $deptMarketing = Department::create(['company_id' => $falcon->id, 'name' => 'Marketing', 'code' => 'MKT', 'description' => 'Brand & Growth Marketing']);
        $deptFinance = Department::create(['company_id' => $falcon->id, 'name' => 'Finance', 'code' => 'FIN', 'description' => 'Treasury, Accounts, & Audits']);
        $deptSales = Department::create(['company_id' => $falcon->id, 'name' => 'Sales', 'code' => 'SLS', 'description' => 'Enterprise & Regional Sales']);
        $deptHR = Department::create(['company_id' => $falcon->id, 'name' => 'Human Resources', 'code' => 'HR', 'description' => 'Workforce & Talent Acquisition']);
        $deptOperations = Department::create(['company_id' => $falcon->id, 'name' => 'Operations', 'code' => 'OPS', 'description' => 'Supply Chain & Procurement']);
        $deptSupport = Department::create(['company_id' => $falcon->id, 'name' => 'Customer Support', 'code' => 'SUP', 'description' => '24/7 Client Success & Helpdesk']);

        // 3. Designations
        $desSeniorDev = Designation::create(['department_id' => $deptEngineering->id, 'title' => 'Senior Principal Architect']);
        $desLeadDev = Designation::create(['department_id' => $deptEngineering->id, 'title' => 'Senior Full Stack Engineer']);
        $desMktManager = Designation::create(['department_id' => $deptMarketing->id, 'title' => 'Growth Marketing Director']);
        $desFinAnalyst = Designation::create(['department_id' => $deptFinance->id, 'title' => 'Senior Financial Controller']);
        $desSalesDir = Designation::create(['department_id' => $deptSales->id, 'title' => 'VP of Enterprise Sales']);
        $desHRLead = Designation::create(['department_id' => $deptHR->id, 'title' => 'Chief People Officer']);
        $desOpsLead = Designation::create(['department_id' => $deptOperations->id, 'title' => 'Global Logistics Director']);
        $desSupLead = Designation::create(['department_id' => $deptSupport->id, 'title' => 'Customer Support Manager']);

        // 4. Roles & Permissions
        $rolesList = [
            'Super Admin' => 'Full unconstrained root access across all modules and admin panels',
            'Admin' => 'Enterprise administrator with system settings and user management privileges',
            'HR Manager' => 'Access to workforce, attendance, recruitment, and payroll management',
            'Inventory Manager' => 'Access to stock, warehouses, suppliers, and inventory movements',
            'CRM Manager' => 'Access to leads, pipelines, customer accounts, and client opportunities',
            'Sales Manager' => 'Access to quotations, sales orders, invoices, and revenue tracking',
            'Finance Manager' => 'Access to ledger, accounting, transactions, and financial analysis',
            'Procurement Manager' => 'Access to purchase orders, supplier payments, and goods receipts',
            'Project Manager' => 'Access to projects, tasks, sprint planning, and timesheet approvals',
            'Support Manager' => 'Access to ticket helpdesk, customer escalations, and SLA monitoring',
            'Employee' => 'Standard self-service employee portal access',
        ];

        $createdRoles = [];
        foreach ($rolesList as $rName => $rDesc) {
            $createdRoles[$rName] = Role::create([
                'name' => $rName,
                'display_name' => $rName,
                'description' => $rDesc,
            ]);
        }

        $modules = ['dashboard', 'hrm', 'inventory', 'crm', 'pos', 'assets', 'documents', 'finance', 'sales', 'procurement', 'projects', 'support', 'users', 'settings'];
        $actions = ['view', 'create', 'edit', 'delete', 'export'];

        $allPermIds = [];
        foreach ($modules as $mod) {
            foreach ($actions as $act) {
                $perm = Permission::create([
                    'name' => "{$mod}.{$act}",
                    'module' => ucfirst($mod),
                    'description' => "Permission to {$act} {$mod}",
                ]);
                $allPermIds[] = $perm->id;
            }
        }
        $createdRoles['Super Admin']->permissions()->sync($allPermIds);
        $createdRoles['Admin']->permissions()->sync($allPermIds);

        // 5. Seed Users
        $defaultPassword = Hash::make('password');

        $adminUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptEngineering->id,
            'name' => 'Alexander Wright',
            'email' => 'admin@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Super Admin',
            'phone' => '+1 (555) 100-0001',
            'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $hrUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptHR->id,
            'name' => 'Sarah Jenkins',
            'email' => 'hr@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'HR Manager',
            'phone' => '+1 (555) 100-0002',
            'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150',
            'is_active' => true,
        ]);

        $invUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptOperations->id,
            'name' => 'Marcus Vance',
            'email' => 'inventory@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Inventory Manager',
            'phone' => '+1 (555) 100-0003',
            'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
            'is_active' => true,
        ]);

        $crmUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptSales->id,
            'name' => 'Elena Rostova',
            'email' => 'crm@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'CRM Manager',
            'phone' => '+1 (555) 100-0004',
            'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150',
            'is_active' => true,
        ]);

        $finUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptFinance->id,
            'name' => 'David Sterling',
            'email' => 'finance@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Finance Manager',
            'phone' => '+1 (555) 100-0005',
            'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150',
            'is_active' => true,
        ]);

        $salesUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptSales->id,
            'name' => 'Rachel Zhang',
            'email' => 'sales@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Sales Manager',
            'phone' => '+1 (555) 100-0006',
            'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150',
            'is_active' => true,
        ]);

        $procUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptOperations->id,
            'name' => 'Thomas Miller',
            'email' => 'procurement@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Procurement Manager',
            'phone' => '+1 (555) 100-0007',
            'avatar' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150',
            'is_active' => true,
        ]);

        $projUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptEngineering->id,
            'name' => 'Liam O\'Connor',
            'email' => 'projects@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Project Manager',
            'phone' => '+1 (555) 100-0008',
            'avatar' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150',
            'is_active' => true,
        ]);

        $supUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptSupport->id,
            'name' => 'Chloe Bennett',
            'email' => 'support@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Support Manager',
            'phone' => '+1 (555) 100-0009',
            'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150',
            'is_active' => true,
        ]);

        $empUser = User::create([
            'company_id' => $falcon->id,
            'department_id' => $deptEngineering->id,
            'name' => 'Lucas Morales',
            'email' => 'employee@falconerp.com',
            'password' => $defaultPassword,
            'role' => 'Employee',
            'phone' => '+1 (555) 100-0010',
            'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150',
            'is_active' => true,
        ]);

        // 6. HRM Employees & Attendance
        $departments = [$deptEngineering, $deptMarketing, $deptFinance, $deptSales, $deptHR, $deptOperations];
        $designations = [$desSeniorDev, $desLeadDev, $desMktManager, $desFinAnalyst, $desSalesDir, $desHRLead, $desOpsLead, $desSupLead];

        $employeeData = [
            ['Alexander', 'Wright', 'alex.wright@falconllp.com', 12500, $deptEngineering->id, $desSeniorDev->id, $adminUser->id],
            ['Sarah', 'Jenkins', 's.jenkins@falconllp.com', 9500, $deptHR->id, $desHRLead->id, $hrUser->id],
            ['Marcus', 'Vance', 'm.vance@falconllp.com', 8800, $deptOperations->id, $desOpsLead->id, $invUser->id],
            ['Elena', 'Rostova', 'e.rostova@falconllp.com', 9200, $deptSales->id, $desSalesDir->id, $crmUser->id],
            ['David', 'Sterling', 'd.sterling@falconllp.com', 10500, $deptFinance->id, $desFinAnalyst->id, $finUser->id],
            ['Rachel', 'Zhang', 'r.zhang@falconllp.com', 9100, $deptSales->id, $desSalesDir->id, $salesUser->id],
            ['Thomas', 'Miller', 't.miller@falconllp.com', 8400, $deptOperations->id, $desOpsLead->id, $procUser->id],
            ['Liam', 'O\'Connor', 'l.oconnor@falconllp.com', 11000, $deptEngineering->id, $desSeniorDev->id, $projUser->id],
            ['Chloe', 'Bennett', 'c.bennett@falconllp.com', 7500, $deptSupport->id, $desSupLead->id, $supUser->id],
            ['Lucas', 'Morales', 'l.morales@falconllp.com', 7800, $deptEngineering->id, $desLeadDev->id, $empUser->id],
            ['Sophia', 'Taylor', 's.taylor@falconllp.com', 8200, $deptMarketing->id, $desMktManager->id, null],
            ['Noah', 'Brooks', 'n.brooks@falconllp.com', 8600, $deptEngineering->id, $desLeadDev->id, null],
            ['Ava', 'Martinez', 'a.martinez@falconllp.com', 7900, $deptFinance->id, $desFinAnalyst->id, null],
            ['Ethan', 'Hunt', 'e.hunt@falconllp.com', 9500, $deptOperations->id, $desOpsLead->id, null],
            ['Mia', 'Chen', 'm.chen@falconllp.com', 8100, $deptSales->id, $desSalesDir->id, null],
            ['James', 'Wilson', 'j.wilson@falconllp.com', 7200, $deptSupport->id, $desSupLead->id, null],
            ['Emily', 'Clark', 'e.clark@falconllp.com', 6900, $deptHR->id, $desHRLead->id, null],
            ['Benjamin', 'Harris', 'b.harris@falconllp.com', 8900, $deptEngineering->id, $desLeadDev->id, null],
        ];

        $createdEmployees = [];
        foreach ($employeeData as $idx => $item) {
            $createdEmployees[] = Employee::create([
                'user_id' => $item[6],
                'company_id' => $falcon->id,
                'department_id' => $item[4],
                'designation_id' => $item[5],
                'employee_code' => sprintf('EMP-%04d', $idx + 101),
                'first_name' => $item[0],
                'last_name' => $item[1],
                'email' => $item[2],
                'phone' => '+1 (555) 200-' . sprintf('%04d', $idx + 1),
                'joining_date' => Carbon::now()->subMonths(18 - $idx),
                'salary' => $item[3],
                'status' => $idx === 10 ? 'on_leave' : ($idx === 14 ? 'on_leave' : 'active'),
                'employment_type' => 'Full-Time',
                'avatar' => "https://i.pravatar.cc/150?u={$item[2]}",
            ]);
        }

        // Attendance records for the past 7 days
        $statuses = ['present', 'present', 'present', 'present', 'late', 'remote', 'absent'];
        for ($day = 6; $day >= 0; $day--) {
            $currentDate = Carbon::today()->subDays($day);
            foreach ($createdEmployees as $eIdx => $emp) {
                $status = $statuses[($eIdx + $day) % count($statuses)];
                if ($emp->status === 'on_leave' && $day <= 2) {
                    $status = 'absent';
                }
                Attendance::create([
                    'employee_id' => $emp->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'check_in' => $status === 'absent' ? null : ($status === 'late' ? '09:42:00' : '08:55:00'),
                    'check_out' => $status === 'absent' ? null : '17:30:00',
                    'status' => $status,
                    'working_hours' => $status === 'absent' ? 0 : ($status === 'late' ? 7.25 : 8.5),
                    'notes' => $status === 'remote' ? 'Work from home' : null,
                ]);
            }
        }

        // Leave Types and Requests
        $annualLeave = LeaveType::create(['name' => 'Annual Leave', 'days_per_year' => 20]);
        $sickLeave = LeaveType::create(['name' => 'Sick Leave', 'days_per_year' => 12]);
        $casualLeave = LeaveType::create(['name' => 'Casual Leave', 'days_per_year' => 10]);

        LeaveRequest::create([
            'employee_id' => $createdEmployees[10]->id,
            'leave_type_id' => $annualLeave->id,
            'start_date' => Carbon::today()->subDays(2),
            'end_date' => Carbon::today()->addDays(3),
            'days_count' => 5,
            'reason' => 'Family vacation',
            'status' => 'approved',
            'approved_by' => $hrUser->id,
        ]);

        LeaveRequest::create([
            'employee_id' => $createdEmployees[14]->id,
            'leave_type_id' => $sickLeave->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(2),
            'days_count' => 3,
            'reason' => 'Flu and doctor appointment',
            'status' => 'approved',
            'approved_by' => $hrUser->id,
        ]);

        // Payroll (Past 6 months trend)
        $months = ['October', 'November', 'December', 'January', 'February', 'March'];
        foreach ($months as $mIndex => $monthName) {
            $year = $mIndex < 3 ? 2025 : 2026;
            foreach ($createdEmployees as $emp) {
                $basic = $emp->salary;
                $allowances = round($basic * 0.12, 2);
                $deductions = round($basic * 0.08, 2);
                $net = $basic + $allowances - $deductions;
                Payroll::create([
                    'employee_id' => $emp->id,
                    'month' => $monthName,
                    'year' => $year,
                    'basic_salary' => $basic,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'net_salary' => $net,
                    'status' => 'paid',
                    'payment_date' => Carbon::createFromDate($year, ($mIndex + 9) % 12 + 1, 28),
                ]);
            }
        }

        // Job Positions & Recruitment Candidates
        $jobDev = JobPosition::create(['department_id' => $deptEngineering->id, 'title' => 'Senior DevOps Engineer', 'vacancies' => 2, 'status' => 'open']);
        $jobSales = JobPosition::create(['department_id' => $deptSales->id, 'title' => 'Key Account Executive', 'vacancies' => 3, 'status' => 'open']);
        $jobUi = JobPosition::create(['department_id' => $deptMarketing->id, 'title' => 'Lead Product Designer', 'vacancies' => 1, 'status' => 'open']);

        $candidatesData = [
            [$jobDev->id, 'Jonathan Pierce', 'j.pierce@devmail.com', '+1 (555) 301-4412', '7 years', 'interview', 11500],
            [$jobDev->id, 'Samantha Reed', 's.reed@codemail.com', '+1 (555) 301-8891', '5 years', 'screening', 10500],
            [$jobSales->id, 'Robert Sterling Jr', 'r.sterling@bizmail.com', '+1 (555) 301-9921', '6 years', 'selected', 9500],
            [$jobSales->id, 'Melissa Torres', 'm.torres@salemail.com', '+1 (555) 301-7714', '4 years', 'applied', 8800],
            [$jobUi->id, 'Kevin Vanhoutte', 'kevin@uxportfolio.io', '+1 (555) 301-2299', '8 years', 'hired', 12000],
        ];
        foreach ($candidatesData as $c) {
            Candidate::create([
                'job_position_id' => $c[0],
                'name' => $c[1],
                'email' => $c[2],
                'phone' => $c[3],
                'experience' => $c[4],
                'stage' => $c[5],
                'expected_salary' => $c[6],
                'applied_date' => Carbon::today()->subDays(rand(3, 25)),
            ]);
        }

        // 7. Inventory Setup
        $catElectronics = Category::create(['name' => 'Enterprise Hardware', 'slug' => 'enterprise-hardware']);
        $catNetwork = Category::create(['name' => 'Networking & Telecom', 'slug' => 'networking-telecom']);
        $catPeripherals = Category::create(['name' => 'Peripherals & Accessories', 'slug' => 'peripherals-accessories']);
        $catSoftware = Category::create(['name' => 'Licenses & Appliances', 'slug' => 'licenses-appliances']);

        $brandDell = Brand::create(['name' => 'Dell Technologies', 'slug' => 'dell']);
        $brandCisco = Brand::create(['name' => 'Cisco Systems', 'slug' => 'cisco']);
        $brandApple = Brand::create(['name' => 'Apple Inc', 'slug' => 'apple']);
        $brandLogi = Brand::create(['name' => 'Logitech', 'slug' => 'logitech']);

        $unitPcs = Unit::create(['name' => 'Pieces', 'short_name' => 'pcs']);
        $unitBoxes = Unit::create(['name' => 'Boxes', 'short_name' => 'box']);
        $unitSets = Unit::create(['name' => 'Sets', 'short_name' => 'set']);

        $whCentral = Warehouse::create([
            'company_id' => $falcon->id,
            'name' => 'Central Hub Warehouse',
            'code' => 'WH-SF-01',
            'location' => 'San Francisco, CA',
            'manager_name' => 'Marcus Vance',
            'phone' => '+1 (555) 441-9021',
            'status' => 'active',
        ]);

        $whEast = Warehouse::create([
            'company_id' => $falcon->id,
            'name' => 'East Coast Logistics Center',
            'code' => 'WH-NJ-02',
            'location' => 'Newark, NJ',
            'manager_name' => 'George Bradley',
            'phone' => '+1 (555) 441-8842',
            'status' => 'active',
        ]);

        $whSouth = Warehouse::create([
            'company_id' => $falcon->id,
            'name' => 'Austin Distribution Depot',
            'code' => 'WH-TX-03',
            'location' => 'Austin, TX',
            'manager_name' => 'Valerie Adams',
            'phone' => '+1 (555) 441-3319',
            'status' => 'active',
        ]);

        $suppDell = Supplier::create([
            'company_id' => $falcon->id,
            'name' => 'Dell Global Direct',
            'company_name' => 'Dell Technologies Inc',
            'email' => 'enterprise-orders@dell.com',
            'phone' => '+1 (800) 456-3355',
            'address' => 'One Dell Way, Round Rock, TX 78682',
            'tax_id' => 'TX-9923841',
            'status' => 'active',
        ]);

        $suppCisco = Supplier::create([
            'company_id' => $falcon->id,
            'name' => 'Cisco Distribution Americas',
            'company_name' => 'Cisco Capital Corp',
            'email' => 'sales@cisco-americas.com',
            'phone' => '+1 (800) 553-6387',
            'address' => '170 West Tasman Dr., San Jose, CA 95134',
            'tax_id' => 'CA-3382910',
            'status' => 'active',
        ]);

        $productsData = [
            ['PowerEdge R750 Server', 'SRV-PE-R750', $catElectronics->id, $brandDell->id, 3200.00, 4850.00, 5, 14, 8],
            ['Catalyst 9300 48-Port Switch', 'NET-CAT-9300', $catNetwork->id, $brandCisco->id, 2400.00, 3600.00, 8, 22, 12],
            ['MacBook Pro 16" M3 Max 64GB', 'APL-MBP-16M3', $catElectronics->id, $brandApple->id, 2800.00, 3899.00, 10, 4, 3], // Low stock
            ['UltraSharp 32" 4K USB-C Monitor', 'MON-DEL-U3223', $catPeripherals->id, $brandDell->id, 580.00, 899.00, 15, 38, 20],
            ['Logitech MX Master 3S Wireless', 'PER-LOG-MX3S', $catPeripherals->id, $brandLogi->id, 65.00, 109.00, 20, 84, 45],
            ['Cisco Meraki MR46 Wi-Fi 6 AP', 'WIFI-MR46-ENT', $catNetwork->id, $brandCisco->id, 750.00, 1150.00, 10, 3, 2], // Low stock
            ['Dell Precision 5860 Workstation', 'WKS-DEL-5860', $catElectronics->id, $brandDell->id, 2600.00, 3950.00, 6, 0, 0], // Out of stock
            ['Enterprise Rack Enclosure 42U', 'RCK-APC-42U', $catElectronics->id, $brandDell->id, 1100.00, 1850.00, 4, 11, 6],
            ['Logitech Rally Plus Conference Kit', 'CONF-LOG-RALLY', $catPeripherals->id, $brandLogi->id, 1800.00, 2799.00, 5, 9, 5],
            ['Cisco Secure Firewall 3110', 'SEC-CIS-3110', $catNetwork->id, $brandCisco->id, 4500.00, 6900.00, 3, 7, 4],
        ];

        $createdProducts = [];
        foreach ($productsData as $p) {
            $prod = Product::create([
                'company_id' => $falcon->id,
                'category_id' => $p[2],
                'brand_id' => $p[3],
                'unit_id' => $unitPcs->id,
                'name' => $p[0],
                'sku' => $p[1],
                'barcode' => '880' . rand(10000000, 99999999),
                'cost_price' => $p[4],
                'selling_price' => $p[5],
                'alert_quantity' => $p[6],
                'status' => 'active',
            ]);
            $createdProducts[] = $prod;

            Stock::create(['product_id' => $prod->id, 'warehouse_id' => $whCentral->id, 'quantity' => $p[7]]);
            Stock::create(['product_id' => $prod->id, 'warehouse_id' => $whEast->id, 'quantity' => $p[8]]);

            StockMovement::create([
                'product_id' => $prod->id,
                'warehouse_id' => $whCentral->id,
                'type' => 'in',
                'quantity' => $p[7] + 10,
                'reference' => 'PO-INIT-' . rand(100, 999),
                'notes' => 'Bulk quarterly stock fulfillment',
                'created_by' => $invUser->id,
            ]);
        }

        // 8. CRM Setup
        $custAcme = Customer::create([
            'company_id' => $falcon->id,
            'name' => 'Acme Cloud Dynamics',
            'company_name' => 'Acme Corporation International',
            'email' => 'procurement@acmecloud.com',
            'phone' => '+1 (555) 789-2311',
            'billing_address' => '450 Mission St, San Francisco, CA 94105',
            'shipping_address' => '450 Mission St, San Francisco, CA 94105',
            'credit_limit' => 100000.00,
            'balance' => 24500.00,
            'status' => 'active',
        ]);

        $custCyber = Customer::create([
            'company_id' => $falcon->id,
            'name' => 'CyberPulse Systems',
            'company_name' => 'CyberPulse Security Group',
            'email' => 'accounts@cyberpulseglobal.com',
            'phone' => '+1 (555) 789-9944',
            'billing_address' => '1200 Avenue of the Americas, New York, NY 10036',
            'shipping_address' => 'Warehouse Bay 4, Jersey City, NJ 07302',
            'credit_limit' => 250000.00,
            'balance' => 68200.00,
            'status' => 'active',
        ]);

        $custVanguard = Customer::create([
            'company_id' => $falcon->id,
            'name' => 'Vanguard BioTech',
            'company_name' => 'Vanguard Life Sciences Inc',
            'email' => 'it-orders@vanguardbio.com',
            'phone' => '+1 (555) 789-1123',
            'billing_address' => '800 Technology Square, Cambridge, MA 02139',
            'shipping_address' => '800 Technology Square, Cambridge, MA 02139',
            'credit_limit' => 150000.00,
            'balance' => 12400.00,
            'status' => 'active',
        ]);

        $leadsData = [
            ['Enterprise Core Migration', 'Julian', 'Vance', 'j.vance@solardata.net', '+1 (555) 601-3829', 'SolarData Infrastructure', 'Website', 'qualified', 85000.00],
            ['Campus Network Overhaul', 'Beatrice', 'Monroe', 'b.monroe@metrohealth.org', '+1 (555) 601-7721', 'MetroHealth Hospitals', 'Referral', 'proposal', 145000.00],
            ['Hybrid Datacenter Expansion', 'Arthur', 'Pendleton', 'a.pendleton@fintechbridge.io', '+1 (555) 601-9942', 'Fintech Bridge Partners', 'Cold Call', 'negotiation', 220000.00],
            ['Regional Office Hardware Refresh', 'Claire', 'Delacroix', 'claire@novawave.fr', '+1 (555) 601-1152', 'NovaWave Communications', 'Exhibition', 'won', 95000.00],
            ['Disaster Recovery Node', 'Marcus', 'Briggs', 'm.briggs@aegissecurity.com', '+1 (555) 601-4491', 'Aegis Security Tech', 'Website', 'contacted', 45000.00],
            ['Workstation Fleet Leasing', 'Hannah', 'Gomez', 'h.gomez@crestviewmedia.com', '+1 (555) 601-8833', 'Crestview Studios', 'Referral', 'new', 62000.00],
        ];

        foreach ($leadsData as $ld) {
            Lead::create([
                'company_id' => $falcon->id,
                'assigned_to' => $crmUser->id,
                'title' => $ld[0],
                'first_name' => $ld[1],
                'last_name' => $ld[2],
                'email' => $ld[3],
                'phone' => $ld[4],
                'company' => $ld[5],
                'source' => $ld[6],
                'status' => $ld[7],
                'deal_value' => $ld[8],
                'notes' => 'High-intent enterprise engagement with budget confirmed.',
            ]);
        }

        Opportunity::create([
            'customer_id' => $custAcme->id,
            'assigned_to' => $salesUser->id,
            'title' => 'Cloud Migration Node Tier 3',
            'stage' => 'negotiation',
            'probability' => 80,
            'expected_revenue' => 140000.00,
            'close_date' => Carbon::today()->addDays(20),
        ]);

        Opportunity::create([
            'customer_id' => $custCyber->id,
            'assigned_to' => $salesUser->id,
            'title' => 'SOC 2 Perimeter Firewall Deployment',
            'stage' => 'proposal',
            'probability' => 60,
            'expected_revenue' => 95000.00,
            'close_date' => Carbon::today()->addDays(35),
        ]);

        Opportunity::create([
            'customer_id' => $custVanguard->id,
            'assigned_to' => $salesUser->id,
            'title' => 'Biotech Lab Compute Cluster',
            'stage' => 'closed_won',
            'probability' => 100,
            'expected_revenue' => 280000.00,
            'close_date' => Carbon::today()->subDays(5),
        ]);

        Activity::create([
            'user_id' => $crmUser->id,
            'subjectable_type' => Customer::class,
            'subjectable_id' => $custAcme->id,
            'type' => 'call',
            'subject' => 'Quarterly Business Review & Architecture Check',
            'description' => 'Discussed expansion roadmap and server capacity for Q3.',
            'scheduled_at' => Carbon::today()->addDays(2)->setHour(14),
            'status' => 'pending',
        ]);

        Activity::create([
            'user_id' => $crmUser->id,
            'subjectable_type' => Customer::class,
            'subjectable_id' => $custCyber->id,
            'type' => 'meeting',
            'subject' => 'Contract Finalization with VP of Security',
            'description' => 'Legal and compliance terms alignment.',
            'scheduled_at' => Carbon::today()->addDays(4)->setHour(10),
            'status' => 'pending',
        ]);

        // 9. POS Orders
        $posOrder1 = PosOrder::create([
            'company_id' => $falcon->id,
            'cashier_id' => $empUser->id,
            'customer_id' => $custAcme->id,
            'order_number' => 'POS-2026-001',
            'subtotal' => 1290.00,
            'tax_amount' => 103.20,
            'discount_amount' => 50.00,
            'total_amount' => 1343.20,
            'payment_method' => 'card',
            'status' => 'completed',
        ]);
        PosOrderItem::create(['pos_order_id' => $posOrder1->id, 'product_id' => $createdProducts[3]->id, 'quantity' => 1, 'unit_price' => 899.00, 'total_price' => 899.00]);
        PosOrderItem::create(['pos_order_id' => $posOrder1->id, 'product_id' => $createdProducts[4]->id, 'quantity' => 2, 'unit_price' => 109.00, 'total_price' => 218.00]);
        PosTransaction::create(['pos_order_id' => $posOrder1->id, 'amount' => 1343.20, 'payment_type' => 'card', 'reference_no' => 'TXN-VISA-99482']);

        $posOrder2 = PosOrder::create([
            'company_id' => $falcon->id,
            'cashier_id' => $empUser->id,
            'customer_id' => $custCyber->id,
            'order_number' => 'POS-2026-002',
            'subtotal' => 3899.00,
            'tax_amount' => 311.92,
            'discount_amount' => 0.00,
            'total_amount' => 4210.92,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        PosOrderItem::create(['pos_order_id' => $posOrder2->id, 'product_id' => $createdProducts[2]->id, 'quantity' => 1, 'unit_price' => 3899.00, 'total_price' => 3899.00]);
        PosTransaction::create(['pos_order_id' => $posOrder2->id, 'amount' => 4210.92, 'payment_type' => 'cash', 'reference_no' => 'CASH-REC-1002']);

        $posOrder3 = PosOrder::create([
            'company_id' => $falcon->id,
            'cashier_id' => $empUser->id,
            'customer_id' => null,
            'order_number' => 'POS-2026-003',
            'subtotal' => 436.00,
            'tax_amount' => 34.88,
            'discount_amount' => 20.00,
            'total_amount' => 450.88,
            'payment_method' => 'card',
            'status' => 'completed',
        ]);
        PosOrderItem::create(['pos_order_id' => $posOrder3->id, 'product_id' => $createdProducts[4]->id, 'quantity' => 4, 'unit_price' => 109.00, 'total_price' => 436.00]);
        PosTransaction::create(['pos_order_id' => $posOrder3->id, 'amount' => 450.88, 'payment_type' => 'card', 'reference_no' => 'TXN-MC-88124']);

        // 10. Finance Setup (Accounts, Transactions, Invoices)
        $accOperating = Account::create(['company_id' => $falcon->id, 'account_name' => 'JPMorgan Operating Account', 'account_number' => '****-****-4821', 'type' => 'bank', 'balance' => 842900.50, 'currency' => 'USD']);
        $accTreasury = Account::create(['company_id' => $falcon->id, 'account_name' => 'Silicon Valley Treasury Reserve', 'account_number' => '****-****-9102', 'type' => 'bank', 'balance' => 1450000.00, 'currency' => 'USD']);
        $accStripe = Account::create(['company_id' => $falcon->id, 'account_name' => 'Stripe Merchant Settlement', 'account_number' => 'acct_1M89s9', 'type' => 'card', 'balance' => 68420.00, 'currency' => 'USD']);
        $accPetty = Account::create(['company_id' => $falcon->id, 'account_name' => 'Main Office Petty Cash', 'account_number' => 'CASH-SF-01', 'type' => 'cash', 'balance' => 4500.00, 'currency' => 'USD']);

        $expensesData = [
            ['Payroll & Benefits', 165000.00, 'EXP-PAY-03', 'Monthly payroll disbursements for workforce'],
            ['AWS Cloud Infrastructure', 28400.00, 'EXP-AWS-882', 'Production multi-region cluster hosting'],
            ['Office Lease & Utilities', 18500.00, 'EXP-RENT-03', 'SF HQ Tower 742 lease and internet fiber'],
            ['Marketing & Enterprise Ad Spend', 24200.00, 'EXP-MKT-03', 'Google, LinkedIn, and Gartner sponsorship'],
            ['Hardware Logistics & Freight', 12300.00, 'EXP-LOG-03', 'Air and ground transport for server shipments'],
        ];

        foreach ($expensesData as $ex) {
            Expense::create([
                'company_id' => $falcon->id,
                'account_id' => $accOperating->id,
                'category' => $ex[0],
                'amount' => $ex[1],
                'expense_date' => Carbon::today()->subDays(rand(2, 20)),
                'reference' => $ex[2],
                'description' => $ex[3],
                'status' => 'paid',
            ]);
            Transaction::create([
                'account_id' => $accOperating->id,
                'type' => 'expense',
                'amount' => $ex[1],
                'category' => $ex[0],
                'reference' => $ex[2],
                'description' => $ex[3],
                'transaction_date' => Carbon::today()->subDays(rand(2, 20)),
            ]);
        }

        $incomesData = [
            ['Enterprise Software License Renewal', 185000.00, 'INC-REN-9921', 'Annual platform licensing contract'],
            ['Managed Cloud Engineering Services', 74500.00, 'INC-SRV-4412', 'Tier 1 implementation consulting'],
            ['Hardware Infrastructure Fulfillment', 248000.00, 'INC-HW-8831', 'Datacenter hardware cluster order'],
        ];

        foreach ($incomesData as $inc) {
            Income::create([
                'company_id' => $falcon->id,
                'account_id' => $accOperating->id,
                'source' => $inc[0],
                'amount' => $inc[1],
                'income_date' => Carbon::today()->subDays(rand(1, 15)),
                'reference' => $inc[2],
                'description' => $inc[3],
            ]);
            Transaction::create([
                'account_id' => $accOperating->id,
                'type' => 'income',
                'amount' => $inc[1],
                'category' => $inc[0],
                'reference' => $inc[2],
                'description' => $inc[3],
                'transaction_date' => Carbon::today()->subDays(rand(1, 15)),
            ]);
        }

        // Invoices
        $inv1 = Invoice::create([
            'company_id' => $falcon->id,
            'customer_id' => $custAcme->id,
            'invoice_number' => 'INV-2026-0891',
            'issue_date' => Carbon::today()->subDays(12),
            'due_date' => Carbon::today()->addDays(18),
            'subtotal' => 28500.00,
            'tax' => 2280.00,
            'discount' => 500.00,
            'total' => 30280.00,
            'amount_paid' => 30280.00,
            'status' => 'paid',
        ]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'product_id' => $createdProducts[0]->id, 'description' => 'Dell PowerEdge R750 Enterprise Server Cluster', 'quantity' => 6, 'unit_price' => 4750.00, 'tax' => 2280.00, 'total' => 30280.00]);

        $inv2 = Invoice::create([
            'company_id' => $falcon->id,
            'customer_id' => $custCyber->id,
            'invoice_number' => 'INV-2026-0892',
            'issue_date' => Carbon::today()->subDays(5),
            'due_date' => Carbon::today()->addDays(25),
            'subtotal' => 68200.00,
            'tax' => 5456.00,
            'discount' => 1000.00,
            'total' => 72656.00,
            'amount_paid' => 25000.00,
            'status' => 'partially_paid',
        ]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'product_id' => $createdProducts[1]->id, 'description' => 'Cisco Catalyst 9300 Switches with Enterprise Stack', 'quantity' => 18, 'unit_price' => 3600.00, 'tax' => 5456.00, 'total' => 72656.00]);

        $inv3 = Invoice::create([
            'company_id' => $falcon->id,
            'customer_id' => $custVanguard->id,
            'invoice_number' => 'INV-2026-0893',
            'issue_date' => Carbon::today()->subDays(25),
            'due_date' => Carbon::today()->subDays(5),
            'subtotal' => 14800.00,
            'tax' => 1184.00,
            'discount' => 0.00,
            'total' => 15984.00,
            'amount_paid' => 0.00,
            'status' => 'overdue',
        ]);
        InvoiceItem::create(['invoice_id' => $inv3->id, 'product_id' => $createdProducts[3]->id, 'description' => 'Dell UltraSharp 32" Displays and Accessories', 'quantity' => 16, 'unit_price' => 899.00, 'tax' => 1184.00, 'total' => 15984.00]);

        // 11. Sales Orders
        $so1 = SalesOrder::create([
            'company_id' => $falcon->id,
            'customer_id' => $custAcme->id,
            'salesperson_id' => $salesUser->id,
            'order_number' => 'SO-2026-0042',
            'order_date' => Carbon::today()->subDays(3),
            'delivery_date' => Carbon::today()->addDays(7),
            'subtotal' => 48500.00,
            'tax' => 3880.00,
            'discount' => 1500.00,
            'total' => 50880.00,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        SalesOrderItem::create(['sales_order_id' => $so1->id, 'product_id' => $createdProducts[0]->id, 'quantity' => 10, 'unit_price' => 4850.00, 'total' => 48500.00]);

        $so2 = SalesOrder::create([
            'company_id' => $falcon->id,
            'customer_id' => $custCyber->id,
            'salesperson_id' => $salesUser->id,
            'order_number' => 'SO-2026-0043',
            'order_date' => Carbon::today()->subDays(1),
            'delivery_date' => Carbon::today()->addDays(14),
            'subtotal' => 36000.00,
            'tax' => 2880.00,
            'discount' => 1000.00,
            'total' => 37880.00,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
        SalesOrderItem::create(['sales_order_id' => $so2->id, 'product_id' => $createdProducts[1]->id, 'quantity' => 10, 'unit_price' => 3600.00, 'total' => 36000.00]);

        Quotation::create([
            'customer_id' => $custAcme->id,
            'quote_number' => 'QT-2026-0101',
            'date' => Carbon::today(),
            'expiry_date' => Carbon::today()->addDays(30),
            'total' => 92500.00,
            'status' => 'sent',
        ]);

        // 12. Procurement Setup
        $po1 = PurchaseOrder::create([
            'company_id' => $falcon->id,
            'supplier_id' => $suppDell->id,
            'created_by' => $procUser->id,
            'po_number' => 'PO-2026-091',
            'order_date' => Carbon::today()->subDays(6),
            'expected_delivery_date' => Carbon::today()->addDays(5),
            'subtotal' => 32000.00,
            'tax' => 2560.00,
            'total' => 34560.00,
            'status' => 'ordered',
            'payment_status' => 'partial',
        ]);
        PurchaseOrderItem::create(['purchase_order_id' => $po1->id, 'product_id' => $createdProducts[0]->id, 'quantity' => 10, 'unit_price' => 3200.00, 'total' => 32000.00]);
        SupplierPayment::create(['purchase_order_id' => $po1->id, 'supplier_id' => $suppDell->id, 'amount' => 15000.00, 'payment_date' => Carbon::today()->subDays(4), 'payment_method' => 'bank_transfer', 'reference' => 'WIRE-DELL-8821']);

        $po2 = PurchaseOrder::create([
            'company_id' => $falcon->id,
            'supplier_id' => $suppCisco->id,
            'created_by' => $procUser->id,
            'po_number' => 'PO-2026-092',
            'order_date' => Carbon::today()->subDays(2),
            'expected_delivery_date' => Carbon::today()->addDays(12),
            'subtotal' => 48000.00,
            'tax' => 3840.00,
            'total' => 51840.00,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
        PurchaseOrderItem::create(['purchase_order_id' => $po2->id, 'product_id' => $createdProducts[1]->id, 'quantity' => 20, 'unit_price' => 2400.00, 'total' => 48000.00]);

        // 13. Projects & Tasks
        $proj1 = Project::create([
            'company_id' => $falcon->id,
            'client_id' => $custAcme->id,
            'name' => 'Falcon AI Autonomous ERP Engine v2',
            'description' => 'Architecting Next-Gen ERP SaaS platform with microservices, predictive demand analytics, and unified dashboard.',
            'start_date' => Carbon::today()->subDays(45),
            'end_date' => Carbon::today()->addDays(75),
            'budget' => 350000.00,
            'spent' => 142000.00,
            'status' => 'in_progress',
            'progress' => 68,
        ]);

        $proj2 = Project::create([
            'company_id' => $falcon->id,
            'client_id' => $custCyber->id,
            'name' => 'Zero-Trust Secure Perimeter Gateway',
            'description' => 'Implementing hardware security modules, multi-tenant RBAC, and cryptographically verified audit trails.',
            'start_date' => Carbon::today()->subDays(20),
            'end_date' => Carbon::today()->addDays(40),
            'budget' => 180000.00,
            'spent' => 54000.00,
            'status' => 'in_progress',
            'progress' => 42,
        ]);

        $proj3 = Project::create([
            'company_id' => $falcon->id,
            'client_id' => $custVanguard->id,
            'name' => 'Global Automated Supply Chain Pipeline',
            'description' => 'Integrating multi-warehouse stock replenishment with IoT sensors and real-time carrier telemetry.',
            'start_date' => Carbon::today()->subDays(90),
            'end_date' => Carbon::today()->subDays(5),
            'budget' => 220000.00,
            'spent' => 214000.00,
            'status' => 'completed',
            'progress' => 100,
        ]);

        ProjectMember::create(['project_id' => $proj1->id, 'user_id' => $projUser->id, 'role' => 'Project Lead']);
        ProjectMember::create(['project_id' => $proj1->id, 'user_id' => $adminUser->id, 'role' => 'Chief Architect']);
        ProjectMember::create(['project_id' => $proj1->id, 'user_id' => $empUser->id, 'role' => 'Senior Developer']);

        $tasksData = [
            [$proj1->id, $empUser->id, 'Implement Sanctum REST API endpoints for ERP modules', 'high', 'completed', Carbon::today()->subDays(10)],
            [$proj1->id, $empUser->id, 'Build dynamic responsive Tailwind dashboard navigation', 'urgent', 'in_progress', Carbon::today()->addDays(2)],
            [$proj1->id, $adminUser->id, 'Benchmark database query performance on large ledger tables', 'medium', 'todo', Carbon::today()->addDays(8)],
            [$proj1->id, $projUser->id, 'Conduct sprint review and client milestone validation', 'high', 'review', Carbon::today()->addDays(1)],
            [$proj2->id, $empUser->id, 'Verify TLS 1.3 cipher suite and mutual authentication', 'urgent', 'in_progress', Carbon::today()->addDays(4)],
        ];

        foreach ($tasksData as $t) {
            Task::create([
                'project_id' => $t[0],
                'assigned_to' => $t[1],
                'title' => $t[2],
                'priority' => $t[3],
                'status' => $t[4],
                'due_date' => $t[5],
            ]);
        }

        Milestone::create(['project_id' => $proj1->id, 'title' => 'Alpha Release & Core API Freeze', 'due_date' => Carbon::today()->subDays(5), 'status' => 'completed']);
        Milestone::create(['project_id' => $proj1->id, 'title' => 'Beta Client Testing & SLA Validation', 'due_date' => Carbon::today()->addDays(25), 'status' => 'pending']);

        Timesheet::create(['project_id' => $proj1->id, 'user_id' => $empUser->id, 'hours' => 8.0, 'date' => Carbon::today()->subDays(1), 'description' => 'Dashboard UI component styling and responsive layout']);
        Timesheet::create(['project_id' => $proj1->id, 'user_id' => $empUser->id, 'hours' => 7.5, 'date' => Carbon::today()->subDays(2), 'description' => 'REST API controller and service layer integration']);

        // 14. Support Tickets
        $catHardware = TicketCategory::create(['name' => 'Hardware & Infrastructure']);
        $catBilling = TicketCategory::create(['name' => 'Billing & Invoicing']);
        $catAccess = TicketCategory::create(['name' => 'Access & Permissions']);
        $catSoftware = TicketCategory::create(['name' => 'Software & Bugs']);

        $t1 = Ticket::create([
            'company_id' => $falcon->id,
            'customer_id' => $custAcme->id,
            'assigned_agent_id' => $supUser->id,
            'category_id' => $catHardware->id,
            'ticket_number' => 'TCK-2026-101',
            'subject' => 'iDRAC interface unreachable on PowerEdge node 4',
            'description' => 'Management port is unresponsive following firmware security patch.',
            'priority' => 'critical',
            'status' => 'in_progress',
        ]);
        TicketMessage::create(['ticket_id' => $t1->id, 'user_id' => $supUser->id, 'message' => 'Investigating out-of-band switch connection. Scheduled technician visit.', 'is_internal' => false]);

        $t2 = Ticket::create([
            'company_id' => $falcon->id,
            'customer_id' => $custCyber->id,
            'assigned_agent_id' => $supUser->id,
            'category_id' => $catBilling->id,
            'ticket_number' => 'TCK-2026-102',
            'subject' => 'Request for consolidated monthly VAT invoice',
            'description' => 'Please provide updated VAT breakdown for New York jurisdiction.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $t3 = Ticket::create([
            'company_id' => $falcon->id,
            'customer_id' => $custVanguard->id,
            'assigned_agent_id' => $supUser->id,
            'category_id' => $catSoftware->id,
            'ticket_number' => 'TCK-2026-103',
            'subject' => 'API rate limit threshold increase for batch telemetry sync',
            'description' => 'Requesting increase from 1,000 req/min to 5,000 req/min for Q3 research launch.',
            'priority' => 'high',
            'status' => 'resolved',
        ]);
        TicketMessage::create(['ticket_id' => $t3->id, 'user_id' => $supUser->id, 'message' => 'Rate limit increased to 5,000 req/min in production. SLA validated.', 'is_internal' => false]);

        // 15. Audit Logs
        AuditLog::create([
            'user_id' => $adminUser->id,
            'action' => 'LOGIN',
            'module' => 'Authentication',
            'description' => 'Super Admin logged in from workstation (San Francisco)',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        AuditLog::create([
            'user_id' => $salesUser->id,
            'action' => 'CREATE',
            'module' => 'Sales',
            'description' => 'Created Sales Order SO-2026-0042 for Acme Cloud Dynamics ($50,880.00)',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'new_values' => ['order_number' => 'SO-2026-0042', 'total' => 50880.00],
        ]);

        // Seed Applications module data
        $this->call(ApplicationsSeeder::class);

        // Seed Inventory module data
        $this->call(InventorySeeder::class);

        // Seed Enterprise CRM module data
        $this->call(EnterpriseCrmSeeder::class);

        // Seed Enterprise POS, Assets, and Documents modules
        $this->call(EnterpriseNewModulesSeeder::class);
    }
}

