<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeSalaryStructure;
use App\Models\EmployeeTraining;
use App\Models\Holiday;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobPosition;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReview;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnterpriseHrmSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create(['name' => 'Falcon Technologies Inc.', 'email' => 'admin@falconerp.com']);
        $companyId = $company->id;
        $user = User::first();
        $userId = $user?->id ?? 1;

        // 1. 10 Departments
        $deptData = [
            ['name' => 'Executive Management', 'code' => 'EXEC', 'desc' => 'Executive C-Suite leadership and strategy'],
            ['name' => 'Engineering & Technology', 'code' => 'ENG', 'desc' => 'Core software architecture and cloud development'],
            ['name' => 'Product & Design', 'code' => 'PROD', 'desc' => 'Product management and UI/UX design'],
            ['name' => 'Quality Assurance', 'code' => 'QA', 'desc' => 'Test engineering and automation systems'],
            ['name' => 'Human Resources', 'code' => 'HR', 'desc' => 'Workforce engagement, culture and talent acquisition'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN', 'desc' => 'Corporate fiscal governance, ledger and treasury'],
            ['name' => 'Sales & Business Dev', 'code' => 'SALES', 'desc' => 'Global enterprise client acquisition'],
            ['name' => 'Marketing & Growth', 'code' => 'MKTG', 'desc' => 'Brand positioning, digital ads and content'],
            ['name' => 'Customer Success & Support', 'code' => 'CS', 'desc' => '24/7 technical customer resolution and client care'],
            ['name' => 'Legal & Governance', 'code' => 'LEGAL', 'desc' => 'Regulatory compliance and commercial contracts'],
        ];

        $departments = [];
        foreach ($deptData as $d) {
            $departments[] = Department::updateOrCreate(
                ['company_id' => $companyId, 'name' => $d['name']],
                [
                    'department_code' => $d['code'],
                    'description' => $d['desc'],
                    'status' => 'active',
                ]
            );
        }

        // 2. 30 Designations
        $desigData = [
            // Exec
            ['dept' => 0, 'title' => 'Chief Executive Officer', 'code' => 'CEO', 'level' => 'Executive', 'min' => 200000, 'max' => 350000],
            ['dept' => 0, 'title' => 'Chief Technology Officer', 'code' => 'CTO', 'level' => 'Executive', 'min' => 180000, 'max' => 300000],
            ['dept' => 0, 'title' => 'Chief Financial Officer', 'code' => 'CFO', 'level' => 'Executive', 'min' => 170000, 'max' => 290000],
            // Engineering
            ['dept' => 1, 'title' => 'VP of Engineering', 'code' => 'VPE', 'level' => 'Executive', 'min' => 160000, 'max' => 250000],
            ['dept' => 1, 'title' => 'Principal Software Architect', 'code' => 'PSA', 'level' => 'Lead', 'min' => 140000, 'max' => 210000],
            ['dept' => 1, 'title' => 'Engineering Manager', 'code' => 'EM', 'level' => 'Lead', 'min' => 130000, 'max' => 195000],
            ['dept' => 1, 'title' => 'Senior Full Stack Developer', 'code' => 'S-FSD', 'level' => 'Senior', 'min' => 95000, 'max' => 150000],
            ['dept' => 1, 'title' => 'Full Stack Developer', 'code' => 'FSD', 'level' => 'Mid', 'min' => 70000, 'max' => 110000],
            ['dept' => 1, 'title' => 'Senior Frontend Engineer', 'code' => 'S-FE', 'level' => 'Senior', 'min' => 90000, 'max' => 145000],
            ['dept' => 1, 'title' => 'Frontend Developer', 'code' => 'FE', 'level' => 'Mid', 'min' => 65000, 'max' => 105000],
            ['dept' => 1, 'title' => 'Senior Backend Developer', 'code' => 'S-BE', 'level' => 'Senior', 'min' => 90000, 'max' => 145000],
            ['dept' => 1, 'title' => 'DevOps & SRE Engineer', 'code' => 'DEV-OPS', 'level' => 'Senior', 'min' => 95000, 'max' => 150000],
            // Product & Design
            ['dept' => 2, 'title' => 'Director of Product', 'code' => 'DOP', 'level' => 'Lead', 'min' => 135000, 'max' => 200000],
            ['dept' => 2, 'title' => 'Senior Product Manager', 'code' => 'SPM', 'level' => 'Senior', 'min' => 95000, 'max' => 155000],
            ['dept' => 2, 'title' => 'Lead UI/UX Designer', 'code' => 'LEAD-UX', 'level' => 'Lead', 'min' => 85000, 'max' => 140000],
            ['dept' => 2, 'title' => 'UI/UX Designer', 'code' => 'UX', 'level' => 'Mid', 'min' => 60000, 'max' => 95000],
            // QA
            ['dept' => 3, 'title' => 'QA Automation Lead', 'code' => 'QA-LEAD', 'level' => 'Lead', 'min' => 85000, 'max' => 135000],
            ['dept' => 3, 'title' => 'Senior QA Automation Engineer', 'code' => 'S-QA', 'level' => 'Senior', 'min' => 70000, 'max' => 115000],
            ['dept' => 3, 'title' => 'QA Engineer', 'code' => 'QA-ENG', 'level' => 'Mid', 'min' => 50000, 'max' => 85000],
            // HR
            ['dept' => 4, 'title' => 'Director of People & Culture', 'code' => 'HR-DIR', 'level' => 'Lead', 'min' => 110000, 'max' => 170000],
            ['dept' => 4, 'title' => 'Senior HR Business Partner', 'code' => 'HRBP', 'level' => 'Senior', 'min' => 75000, 'max' => 120000],
            ['dept' => 4, 'title' => 'Senior Talent Acquisition Specialist', 'code' => 'TA-SPEC', 'level' => 'Senior', 'min' => 65000, 'max' => 105000],
            // Finance
            ['dept' => 5, 'title' => 'Finance Controller', 'code' => 'FIN-CTRL', 'level' => 'Lead', 'min' => 115000, 'max' => 175000],
            ['dept' => 5, 'title' => 'Senior Financial Analyst', 'code' => 'S-FA', 'level' => 'Senior', 'min' => 80000, 'max' => 125000],
            // Sales
            ['dept' => 6, 'title' => 'VP of Global Enterprise Sales', 'code' => 'VPS', 'level' => 'Executive', 'min' => 150000, 'max' => 250000],
            ['dept' => 6, 'title' => 'Enterprise Account Executive', 'code' => 'AE', 'level' => 'Senior', 'min' => 80000, 'max' => 160000],
            // Marketing
            ['dept' => 7, 'title' => 'Head of Growth Marketing', 'code' => 'HGM', 'level' => 'Lead', 'min' => 110000, 'max' => 165000],
            ['dept' => 7, 'title' => 'Content Marketing Strategist', 'code' => 'CMS', 'level' => 'Mid', 'min' => 60000, 'max' => 95000],
            // CS & Support
            ['dept' => 8, 'title' => 'Head of Customer Success', 'code' => 'HCS', 'level' => 'Lead', 'min' => 100000, 'max' => 155000],
            // Legal
            ['dept' => 9, 'title' => 'General Corporate Counsel', 'code' => 'GCC', 'level' => 'Executive', 'min' => 160000, 'max' => 240000],
        ];

        $designations = [];
        foreach ($desigData as $dg) {
            $deptId = $departments[$dg['dept']]->id ?? null;
            $designations[] = Designation::updateOrCreate(
                ['title' => $dg['title']],
                [
                    'department_id' => $deptId,
                    'code' => $dg['code'],
                    'level' => $dg['level'],
                    'min_salary' => $dg['min'],
                    'max_salary' => $dg['max'],
                    'status' => 'active',
                ]
            );
        }

        // 3. Leave Types (8 types)
        $leaveTypes = [
            ['name' => 'Paid Time Off (PTO)', 'code' => 'PTO', 'days' => 18, 'paid' => true],
            ['name' => 'Sick / Medical Leave', 'code' => 'SICK', 'days' => 12, 'paid' => true],
            ['name' => 'Casual Leave', 'code' => 'CASUAL', 'days' => 10, 'paid' => true],
            ['name' => 'Maternity Leave', 'code' => 'MATERNITY', 'days' => 90, 'paid' => true],
            ['name' => 'Paternity Leave', 'code' => 'PATERNITY', 'days' => 15, 'paid' => true],
            ['name' => 'Compensatory Off', 'code' => 'COMP_OFF', 'days' => 6, 'paid' => true],
            ['name' => 'Bereavement Leave', 'code' => 'BEREAVE', 'days' => 5, 'paid' => true],
            ['name' => 'Leave Without Pay (LWP)', 'code' => 'LWP', 'days' => 30, 'paid' => false],
        ];

        $createdLeaveTypes = [];
        foreach ($leaveTypes as $lt) {
            $createdLeaveTypes[] = LeaveType::updateOrCreate(
                ['name' => $lt['name']],
                [
                    'company_id' => $companyId,
                    'code' => $lt['code'],
                    'days_per_year' => $lt['days'],
                    'is_paid' => $lt['paid'],
                    'carry_forward' => true,
                    'status' => 'active',
                ]
            );
        }

        // 4. 20 Holidays
        $holidayList = [
            ['name' => "New Year's Day", 'date' => '2026-01-01', 'type' => 'public'],
            ['name' => 'Martin Luther King Jr. Day', 'date' => '2026-01-19', 'type' => 'public'],
            ['name' => "Presidents' Day", 'date' => '2026-02-16', 'type' => 'public'],
            ['name' => 'Company Innovation Day', 'date' => '2026-03-12', 'type' => 'company'],
            ['name' => 'Good Friday', 'date' => '2026-04-03', 'type' => 'public'],
            ['name' => 'Memorial Day', 'date' => '2026-05-25', 'type' => 'public'],
            ['name' => 'Juneteenth National Independence Day', 'date' => '2026-06-19', 'type' => 'public'],
            ['name' => 'Independence Day', 'date' => '2026-07-03', 'type' => 'public'],
            ['name' => 'Labor Day', 'date' => '2026-09-07', 'type' => 'public'],
            ['name' => 'Falcon Global Foundation Day', 'date' => '2026-09-28', 'type' => 'company'],
            ['name' => 'Indigenous Peoples Day', 'date' => '2026-10-12', 'type' => 'optional'],
            ['name' => 'Veterans Day', 'date' => '2026-11-11', 'type' => 'public'],
            ['name' => 'Thanksgiving Day', 'date' => '2026-11-26', 'type' => 'public'],
            ['name' => 'Day After Thanksgiving', 'date' => '2026-11-27', 'type' => 'company'],
            ['name' => 'Christmas Eve', 'date' => '2026-12-24', 'type' => 'optional'],
            ['name' => 'Christmas Day', 'date' => '2026-12-25', 'type' => 'public'],
            ['name' => "New Year's Eve", 'date' => '2026-12-31', 'type' => 'company'],
            ['name' => 'Diwali / Festival of Lights', 'date' => '2026-11-08', 'type' => 'optional'],
            ['name' => 'Eid al-Fitr', 'date' => '2026-03-20', 'type' => 'optional'],
            ['name' => 'Wellness & Rest Friday', 'date' => '2026-08-14', 'type' => 'company'],
        ];

        foreach ($holidayList as $h) {
            Holiday::updateOrCreate(
                ['company_id' => $companyId, 'date' => $h['date']],
                [
                    'name' => $h['name'],
                    'type' => $h['type'],
                    'description' => "Official {$h['type']} holiday observed across all departments.",
                    'status' => 'active',
                ]
            );
        }

        // 5. 100 Employees
        $firstNames = ['James', 'Mary', 'Robert', 'Patricia', 'John', 'Jennifer', 'Michael', 'Linda', 'David', 'Elizabeth',
            'William', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica', 'Thomas', 'Sarah', 'Charles', 'Karen',
            'Christopher', 'Nancy', 'Daniel', 'Lisa', 'Matthew', 'Betty', 'Anthony', 'Margaret', 'Mark', 'Sandra',
            'Donald', 'Ashley', 'Steven', 'Kimberly', 'Paul', 'Emily', 'Andrew', 'Donna', 'Joshua', 'Michelle',
            'Kenneth', 'Dorothy', 'Kevin', 'Carol', 'Brian', 'Amanda', 'George', 'Melissa', 'Edward', 'Deborah'];

        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
            'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
            'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson',
            'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores',
            'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts'];

        $banks = ['JPMorgan Chase', 'Bank of America', 'Wells Fargo', 'Citibank', 'PNC Bank', 'US Bank', 'Capital One'];

        $createdEmployees = [];
        for ($e = 1; $e <= 100; $e++) {
            $fName = $firstNames[($e - 1) % count($firstNames)];
            $lName = $lastNames[($e - 1) % count($lastNames)];
            $code = 'EMP-' . str_pad($e, 4, '0', STR_PAD_LEFT);
            $email = strtolower($fName . '.' . $lName . $e . '@falconerp.com');
            $dept = $departments[($e - 1) % count($departments)];
            $desig = $designations[($e - 1) % count($designations)];
            $salary = rand(45000, 165000);
            $empStatus = ($e > 95) ? 'on_leave' : (($e > 92) ? 'resigned' : 'active');

            $emp = Employee::updateOrCreate(
                ['company_id' => $companyId, 'employee_code' => $code],
                [
                    'first_name' => $fName,
                    'last_name' => $lName,
                    'middle_name' => 'A.',
                    'gender' => ($e % 2 == 0) ? 'female' : 'male',
                    'date_of_birth' => Carbon::parse('1990-05-15')->subYears($e % 20),
                    'email' => $email,
                    'phone' => '+1 (555) ' . rand(100, 999) . '-' . rand(1000, 9999),
                    'alternate_phone' => '+1 (555) ' . rand(100, 999) . '-' . rand(1000, 9999),
                    'joining_date' => Carbon::parse('2023-01-15')->addDays($e * 10),
                    'department_id' => $dept->id,
                    'designation_id' => $desig->id,
                    'employment_type' => ($e % 10 == 0) ? 'Contract' : (($e % 15 == 0) ? 'Part-Time' : 'Full-Time'),
                    'work_location' => ($e % 3 == 0) ? 'Remote' : 'Headquarters',
                    'salary' => $salary,
                    'pay_frequency' => 'monthly',
                    'status' => $empStatus,
                    'avatar' => "https://images.unsplash.com/photo-" . (1500000000000 + ($e * 12345)) . "?w=150",
                    'bank_name' => $banks[($e - 1) % count($banks)],
                    'account_holder' => "{$fName} {$lName}",
                    'account_number' => '4400' . str_pad($e, 8, '0', STR_PAD_LEFT),
                    'ifsc' => 'CHASUS33XXX',
                    'pan' => 'FALCP' . rand(1000, 9999) . 'Z',
                    'aadhaar' => '9988 ' . rand(1000, 9999) . ' ' . rand(1000, 9999),
                    'emergency_contact_name' => "Jane {$lName}",
                    'emergency_contact_relationship' => 'Spouse',
                    'emergency_contact_phone' => '+1 (555) 890-1234',
                    'address' => 'Suite ' . ($e * 10) . ', Falcon Towers, Silicon Valley',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'country' => 'US',
                    'postal_code' => '95110',
                ]
            );

            // Salary Structure
            EmployeeSalaryStructure::updateOrCreate(
                ['company_id' => $companyId, 'employee_id' => $emp->id],
                [
                    'basic_salary' => round($salary * 0.50, 2),
                    'hra' => round($salary * 0.20, 2),
                    'transport_allowance' => 2000,
                    'medical_allowance' => 1500,
                    'special_allowance' => 2500,
                    'other_allowances' => 1000,
                    'pf' => round($salary * 0.06, 2),
                    'esi' => round($salary * 0.0075, 2),
                    'professional_tax' => 200,
                    'tds' => round($salary * 0.08, 2),
                    'effective_date' => Carbon::parse('2026-01-01'),
                ]
            );

            // Leave Balances for 2026
            foreach ($createdLeaveTypes as $lt) {
                LeaveBalance::updateOrCreate(
                    ['company_id' => $companyId, 'employee_id' => $emp->id, 'leave_type_id' => $lt->id, 'year' => 2026],
                    [
                        'allocated' => $lt->days_per_year,
                        'used' => rand(0, 3),
                        'pending' => 0,
                        'remaining' => $lt->days_per_year - rand(0, 3),
                    ]
                );
            }

            $createdEmployees[] = $emp;
        }

        // 6. 500 Attendance Records (Spread across active employees over past 10 days)
        $today = Carbon::today();
        $attCount = 0;
        for ($day = 0; $day < 10; $day++) {
            $curDate = (clone $today)->subDays($day);
            if ($curDate->isWeekend()) continue;

            foreach ($createdEmployees as $idx => $emp) {
                if ($attCount >= 500) break 2;
                $isLate = ($idx % 8 == 0);
                $isAbsent = ($idx % 25 == 0);
                $status = $isAbsent ? 'absent' : ($isLate ? 'late' : 'present');
                $checkIn = $isAbsent ? null : ($isLate ? '09:28:00' : '08:55:00');
                $checkOut = $isAbsent ? null : '17:35:00';
                $workMinutes = $isAbsent ? 0 : ($isLate ? 487 : 520);

                Attendance::updateOrCreate(
                    ['company_id' => $companyId, 'employee_id' => $emp->id, 'date' => $curDate->format('Y-m-d')],
                    [
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'working_minutes' => $workMinutes,
                        'working_hours' => round($workMinutes / 60, 2),
                        'late_minutes' => $isLate ? 28 : 0,
                        'overtime_minutes' => $isAbsent ? 0 : max(0, $workMinutes - 480),
                        'status' => $status,
                        'approved_by' => $userId,
                    ]
                );
                $attCount++;
            }
        }

        // 7. 50 Leave Requests
        for ($lr = 1; $lr <= 50; $lr++) {
            $emp = $createdEmployees[($lr - 1) % count($createdEmployees)];
            $lType = $createdLeaveTypes[($lr - 1) % count($createdLeaveTypes)];
            $startDate = Carbon::today()->addDays(($lr % 2 == 0 ? 1 : -1) * rand(2, 30));
            $days = rand(1, 4);
            $endDate = (clone $startDate)->addDays($days - 1);
            $st = ($lr % 4 == 0) ? 'pending' : (($lr % 7 == 0) ? 'rejected' : 'approved');

            LeaveRequest::create([
                'company_id' => $companyId,
                'employee_id' => $emp->id,
                'leave_type_id' => $lType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_count' => $days,
                'reason' => 'Annual family vacation and rest leave request.',
                'status' => $st,
                'approved_by' => $st === 'approved' ? $userId : null,
                'approved_at' => $st === 'approved' ? Carbon::now() : null,
            ]);
        }

        // 8. 6 Payroll Periods & 100 Payroll Records
        $months = [
            ['name' => 'October 2025 Payroll', 'start' => '2025-10-01', 'end' => '2025-10-31', 'pay' => '2025-11-05'],
            ['name' => 'November 2025 Payroll', 'start' => '2025-11-01', 'end' => '2025-11-30', 'pay' => '2025-12-05'],
            ['name' => 'December 2025 Payroll', 'start' => '2025-12-01', 'end' => '2025-12-31', 'pay' => '2026-01-05'],
            ['name' => 'January 2026 Payroll', 'start' => '2026-01-01', 'end' => '2026-01-31', 'pay' => '2026-02-05'],
            ['name' => 'February 2026 Payroll', 'start' => '2026-02-01', 'end' => '2026-02-28', 'pay' => '2026-03-05'],
            ['name' => 'March 2026 Payroll', 'start' => '2026-03-01', 'end' => '2026-03-31', 'pay' => '2026-04-05'],
        ];

        $payrollPeriods = [];
        foreach ($months as $m) {
            $payrollPeriods[] = PayrollPeriod::updateOrCreate(
                ['company_id' => $companyId, 'name' => $m['name']],
                [
                    'start_date' => $m['start'],
                    'end_date' => $m['end'],
                    'payment_date' => $m['pay'],
                    'status' => 'paid',
                ]
            );
        }

        // Generate 100 Payroll records across employees for March 2026 Period
        $activePeriod = $payrollPeriods[5]; // March 2026
        foreach ($createdEmployees as $idx => $emp) {
            $basic = round($emp->salary * 0.50, 2);
            $allow = round($emp->salary * 0.25, 2);
            $gross = $basic + $allow;
            $ded = round($emp->salary * 0.12, 2);
            $net = $gross - $ded;

            $pr = Payroll::updateOrCreate(
                ['company_id' => $companyId, 'employee_id' => $emp->id, 'payroll_period_id' => $activePeriod->id],
                [
                    'month' => 'March',
                    'year' => 2026,
                    'basic_salary' => $basic,
                    'allowances' => $allow,
                    'gross_salary' => $gross,
                    'deductions' => $ded,
                    'overtime_amount' => 0,
                    'net_salary' => $net,
                    'working_days' => 31,
                    'present_days' => 29,
                    'absent_days' => 0,
                    'paid_leaves' => 2,
                    'unpaid_leaves' => 0,
                    'status' => 'paid',
                    'payment_date' => '2026-04-05',
                    'processed_at' => Carbon::now(),
                    'paid_at' => Carbon::now(),
                ]
            );

            Payslip::updateOrCreate(
                ['payroll_id' => $pr->id],
                [
                    'payslip_number' => 'PS-2026-MAR-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT),
                    'generated_at' => Carbon::now(),
                ]
            );
        }

        // 9. 20 Job Positions
        $jobTitles = [
            'Lead Cloud Architect (GCP/AWS)', 'Staff Backend Systems Engineer', 'Senior React Frontend Specialist',
            'Full Stack Enterprise Engineer', 'AI/ML Machine Learning Lead', 'Product Marketing Manager',
            'Enterprise Account Executive', 'Customer Success Director', 'Senior DevOps & Terraform Engineer',
            'QA Automation Lead (Cypress/Playwright)', 'Senior Data Engineer (Snowflake)', 'Principal UX Researcher',
            'Cybersecurity & SOC Specialist', 'Technical Content Strategist', 'Human Resources Business Partner',
            'Corporate Treasury Analyst', 'Senior Product Designer', 'Inbound SDR Representative',
            'Legal Contracts Manager', 'Scrum Master / Agile Delivery Lead'
        ];

        $createdJobs = [];
        foreach ($jobTitles as $jIdx => $jTitle) {
            $dept = $departments[$jIdx % count($departments)];
            $desig = $designations[$jIdx % count($designations)];

            $createdJobs[] = JobPosition::updateOrCreate(
                ['company_id' => $companyId, 'title' => $jTitle],
                [
                    'job_code' => 'JOB-2026-' . str_pad($jIdx + 1, 3, '0', STR_PAD_LEFT),
                    'department_id' => $dept->id,
                    'designation_id' => $desig->id,
                    'location' => ($jIdx % 2 == 0) ? 'San Jose, CA' : 'Remote US',
                    'employment_type' => 'Full-Time',
                    'experience' => '5+ Years',
                    'min_salary' => 90000,
                    'max_salary' => 175000,
                    'vacancies' => rand(1, 4),
                    'status' => ($jIdx > 17) ? 'closed' : 'open',
                    'requirements' => 'Bachelor in CS or related, proven enterprise production track record.',
                    'description' => "Exciting opportunity to join Falcon ERP's core {$dept->name} squad.",
                ]
            );
        }

        // 10. 100 Candidates & 50 Applications
        $candidateNames = [
            'Alexander Wright', 'Sophia Chen', 'Liam Vance', 'Olivia Taylor', 'Noah Bennett',
            'Emma Wilson', 'Ethan Miller', 'Ava Martinez', 'Lucas Anderson', 'Isabella Thomas',
            'Mason Jackson', 'Mia White', 'Oliver Harris', 'Charlotte Clark', 'Aiden Lewis',
            'Amelia Robinson', 'Elijah Walker', 'Harper Young', 'Jameson Allen', 'Evelyn King'
        ];

        $stages = ['applied', 'screening', 'shortlisted', 'interview', 'selected', 'offer', 'hired'];

        for ($c = 1; $c <= 100; $c++) {
            $baseName = $candidateNames[($c - 1) % count($candidateNames)];
            $name = $baseName . ' ' . $c;
            $job = $createdJobs[($c - 1) % count($createdJobs)];
            $stage = $stages[($c - 1) % count($stages)];

            $cand = Candidate::updateOrCreate(
                ['company_id' => $companyId, 'email' => "candidate{$c}@applytalent.com"],
                [
                    'job_position_id' => $job->id,
                    'name' => $name,
                    'phone' => '+1 (555) 456-' . str_pad($c, 4, '0', STR_PAD_LEFT),
                    'experience' => '6 years',
                    'skills' => 'React, Node.js, Laravel, PostgreSQL, Docker, AWS',
                    'location' => 'San Francisco, CA',
                    'source' => ($c % 3 == 0) ? 'LinkedIn' : (($c % 2 == 0) ? 'Referral' : 'Careers Portal'),
                    'stage' => $stage,
                    'expected_salary' => 125000.00,
                    'applied_date' => Carbon::today()->subDays($c),
                ]
            );

            if ($c <= 50) {
                JobApplication::updateOrCreate(
                    ['company_id' => $companyId, 'candidate_id' => $cand->id, 'job_position_id' => $job->id],
                    [
                        'applied_date' => Carbon::today()->subDays($c),
                        'stage' => $stage,
                        'rating' => rand(3, 5),
                        'notes' => 'Exceptional technical depth and strong culture fit.',
                        'status' => 'active',
                    ]
                );
            }

            // 20 Interviews
            if ($c <= 20) {
                Interview::updateOrCreate(
                    ['company_id' => $companyId, 'candidate_id' => $cand->id, 'job_position_id' => $job->id],
                    [
                        'interview_type' => ($c % 2 == 0) ? 'technical' : 'video',
                        'interview_date' => Carbon::today()->addDays($c % 7),
                        'interview_time' => '14:00:00',
                        'interviewers' => 'VP of Engineering & Lead Architect',
                        'meeting_link' => 'https://meet.falconerp.com/int-' . $c,
                        'feedback' => 'Demonstrated deep algorithmic expertise and stellar architectural communication.',
                        'rating' => 5,
                        'status' => 'scheduled',
                    ]
                );
            }

            // 10 Offers
            if ($c <= 10) {
                JobOffer::updateOrCreate(
                    ['company_id' => $companyId, 'candidate_id' => $cand->id, 'job_position_id' => $job->id],
                    [
                        'offer_date' => Carbon::today()->subDays(2),
                        'joining_date' => Carbon::today()->addDays(20),
                        'salary' => 135000.00,
                        'benefits' => 'Competitive Tier-1 Health Insurance, 401(k) 5% Match, Unlimited PTO',
                        'status' => 'sent',
                    ]
                );
            }
        }

        // 11. 5 Performance Cycles
        for ($p = 2022; $p <= 2026; $p++) {
            $isCur = ($p == 2026);
            $cycle = PerformanceCycle::updateOrCreate(
                ['company_id' => $companyId, 'title' => "{$p} Annual Performance Evaluation Cycle"],
                [
                    'year' => $p,
                    'start_date' => "{$p}-01-01",
                    'end_date' => "{$p}-12-31",
                    'status' => $isCur ? 'active' : 'closed',
                ]
            );

            // Goals & reviews for current cycle
            if ($isCur) {
                foreach (array_slice($createdEmployees, 0, 10) as $eKey => $emp) {
                    PerformanceGoal::updateOrCreate(
                        ['company_id' => $companyId, 'cycle_id' => $cycle->id, 'employee_id' => $emp->id],
                        [
                            'title' => 'Deliver Falcon ERP v2.0 Microservices Migration',
                            'kpi' => 'Zero downtime deployment and 99.99% availability SLA',
                            'weight' => 25,
                            'target_value' => '100% Target',
                            'achieved_value' => '94% Achieved',
                            'status' => 'in_progress',
                        ]
                    );

                    PerformanceReview::updateOrCreate(
                        ['company_id' => $companyId, 'cycle_id' => $cycle->id, 'employee_id' => $emp->id],
                        [
                            'reviewer_id' => $userId,
                            'self_rating' => 4,
                            'self_comments' => 'Met all Sprint targets and mentored junior developers across team.',
                            'manager_rating' => 5,
                            'manager_comments' => 'Outstanding leadership, dependable execution and high code velocity.',
                            'final_rating' => 5,
                            'final_score' => 4.80,
                            'status' => 'completed',
                        ]
                    );
                }
            }
        }

        // 12. 20 Training Programs
        $trainingNames = [
            'Enterprise React 19 & Next.js Advanced Architecture', 'Laravel 12 Microservices & Queue Scalability',
            'AWS Certified Solutions Architect Bootcamp', 'Modern CI/CD Pipelines with GitHub Actions & Docker',
            'Cybersecurity Awareness & SOC2 Compliance Training', 'Agile Scrum Leadership & Sprint Planning',
            'PostgreSQL Performance Tuning & Index Optimization', 'Enterprise UI/UX Design Systems with Figma',
            'Executive Communication & Public Speaking Workshop', 'Effective Conflict Resolution for Team Leads',
            'Kubernetes Cluster Orchestration & Helm Deployment', 'Machine Learning Foundations for Engineers',
            'Financial Literacy & Corporate Budgeting Basics', 'Customer Retention & CSAT Elevation Strategies',
            'Data Protection & GDPR / CCPA Legal Governance', 'API Security & OAuth2 / OpenID Connect Standards',
            'Full Stack Test Automation with Playwright', 'Enterprise Sales Negotiation & Solution Selling',
            'Product Discovery & OKR Metric Management', 'High-Impact Technical Mentorship & Leadership'
        ];

        foreach ($trainingNames as $tIdx => $tTitle) {
            $prog = TrainingProgram::updateOrCreate(
                ['company_id' => $companyId, 'title' => $tTitle],
                [
                    'trainer' => 'Dr. Marcus Vance (Senior Industry Fellow)',
                    'description' => "Comprehensive hands-on curriculum focused on {$tTitle}.",
                    'start_date' => Carbon::today()->addDays($tIdx * 5),
                    'end_date' => Carbon::today()->addDays(($tIdx * 5) + 3),
                    'duration_hours' => 16,
                    'location' => 'Executive Innovation Hall & Virtual',
                    'cost' => rand(1500, 8000),
                    'status' => ($tIdx % 3 == 0) ? 'active' : 'planned',
                ]
            );

            // Add enrollments
            foreach (array_slice($createdEmployees, $tIdx * 3, 4) as $emp) {
                EmployeeTraining::updateOrCreate(
                    ['company_id' => $companyId, 'program_id' => $prog->id, 'employee_id' => $emp->id],
                    [
                        'enrollment_date' => Carbon::today(),
                        'completion_status' => 'enrolled',
                    ]
                );
            }
        }
    }
}
