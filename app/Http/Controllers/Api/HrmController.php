<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Holiday;
use App\Models\JobPosition;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Services\AttendanceService;
use App\Services\HrAnalyticsService;
use App\Services\HrmService;
use App\Services\LeaveService;
use App\Services\PayrollService;
use App\Services\PerformanceService;
use App\Services\RecruitmentService;
use App\Services\TrainingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrmController extends Controller
{
    protected HrmService $hrmService;
    protected AttendanceService $attendanceService;
    protected LeaveService $leaveService;
    protected PayrollService $payrollService;
    protected RecruitmentService $recruitmentService;
    protected PerformanceService $performanceService;
    protected TrainingService $trainingService;
    protected HrAnalyticsService $analyticsService;

    public function __construct(
        HrmService $hrmService,
        AttendanceService $attendanceService,
        LeaveService $leaveService,
        PayrollService $payrollService,
        RecruitmentService $recruitmentService,
        PerformanceService $performanceService,
        TrainingService $trainingService,
        HrAnalyticsService $analyticsService
    ) {
        $this->hrmService = $hrmService;
        $this->attendanceService = $attendanceService;
        $this->leaveService = $leaveService;
        $this->payrollService = $payrollService;
        $this->recruitmentService = $recruitmentService;
        $this->performanceService = $performanceService;
        $this->trainingService = $trainingService;
        $this->analyticsService = $analyticsService;
    }

    protected function getCompanyId(Request $request): ?int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    /**
     * HRM Dashboard Overview
     */
    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->hrmService->getDashboardData($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // ==========================================
    // EMPLOYEES
    // ==========================================

    public function employees(Request $request): JsonResponse
    {
        $query = Employee::with(['department', 'designation', 'manager']);

        if ($cid = $this->getCompanyId($request)) {
            $query->where('company_id', $cid);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($dept = $request->get('department_id')) {
            $query->where('department_id', $dept);
        }

        if ($desig = $request->get('designation_id')) {
            $query->where('designation_id', $desig);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Return all employees when explicitly requested (e.g., dropdowns/selects) or order by latest
        if ($request->boolean('all') || $request->get('per_page') === 'all') {
            $employees = $query->orderBy('first_name')->get();
            return response()->json(['status' => 'success', 'data' => $employees]);
        }

        $perPage = min((int) $request->get('per_page', 15), 500);
        $employees = $query->latest()->paginate($perPage);
        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string',
            'gender' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'manager_id' => 'nullable|exists:employees,id',
            'employment_type' => 'nullable|string',
            'work_location' => 'nullable|string',
            'salary' => 'nullable|numeric|min:0',
            'pay_frequency' => 'nullable|string',
            'status' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_holder' => 'nullable|string',
            'account_number' => 'nullable|string',
            'ifsc' => 'nullable|string',
            'pan' => 'nullable|string',
            'aadhaar' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_relationship' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $validated['company_id'] = $companyId;

        // Auto-generate unique employee_code or respect submitted one
        if (!empty($request->employee_code)) {
            $code = $request->employee_code;
            if (Employee::withTrashed()->where('employee_code', $code)->exists()) {
                $code = 'EMP-' . str_pad((string) ((int) (Employee::withTrashed()->max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
            }
            $validated['employee_code'] = $code;
        } else {
            $validated['employee_code'] = 'EMP-' . str_pad((string) ((int) (Employee::withTrashed()->max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
        }

        $employee = Employee::create($validated);

        // Auto create base salary structure
        $employee->salaryStructure()->create([
            'company_id' => $companyId,
            'basic_salary' => round(($validated['salary'] ?? 50000) * 0.50, 2),
            'hra' => round(($validated['salary'] ?? 50000) * 0.20, 2),
            'transport_allowance' => 2000,
            'medical_allowance' => 1500,
            'special_allowance' => 2500,
            'pf' => round(($validated['salary'] ?? 50000) * 0.06, 2),
            'esi' => round(($validated['salary'] ?? 50000) * 0.0075, 2),
            'effective_date' => Carbon::today(),
        ]);

        // Auto-provision User Account for System & User Management
        $createdUser = null;
        if (!empty($request->password) || $request->boolean('create_user_account', true)) {
            $userPassword = !empty($request->password) ? $request->password : 'Welcome@123';
            
            // Map role from request, or default intelligently from Department name
            $userRole = !empty($request->role) ? $request->role : null;
            if (!$userRole && !empty($validated['department_id'])) {
                $dep = Department::find($validated['department_id']);
                if ($dep) {
                    $depName = strtolower($dep->name);
                    if (str_contains($depName, 'hr') || str_contains($depName, 'human')) $userRole = 'HR Manager';
                    elseif (str_contains($depName, 'store') || str_contains($depName, 'warehouse')) $userRole = 'Store Executive';
                    elseif (str_contains($depName, 'service')) $userRole = 'Service Engineer';
                    elseif (str_contains($depName, 'licens')) $userRole = 'Licensing Manager';
                    elseif (str_contains($depName, 'sales')) $userRole = 'Sales Manager';
                    elseif (str_contains($depName, 'purchase') || str_contains($depName, 'procurement')) $userRole = 'Purchase Manager';
                    elseif (str_contains($depName, 'doc') || str_contains($depName, 'quality') || str_contains($depName, 'qc')) $userRole = 'Document Manager / Checker';
                    elseif (str_contains($depName, 'project')) $userRole = 'Project Engineer';
                }
            }
            if (!$userRole) {
                $userRole = 'Project Engineer';
            }

            $fullName = trim($validated['first_name'] . ' ' . ($validated['middle_name'] ?? '') . ' ' . $validated['last_name']);

            // Check if user already exists with this email
            $existingUser = User::where('email', $validated['email'])->first();
            if (!$existingUser) {
                $createdUser = User::create([
                    'company_id' => $companyId,
                    'department_id' => $validated['department_id'] ?? null,
                    'name' => $fullName,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'role' => $userRole,
                    'is_active' => true,
                    'password' => Hash::make($userPassword),
                ]);

                // Link employee to user_id
                $employee->user_id = $createdUser->id;
                $employee->save();
            } else {
                // Link employee to existing user
                $employee->user_id = $existingUser->id;
                $employee->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created and User account provisioned successfully',
            'data' => $employee->fresh(['department', 'designation', 'user']),
            'user' => $createdUser ? [
                'id' => $createdUser->id,
                'email' => $createdUser->email,
                'role' => $createdUser->role,
            ] : null,
        ], 201);
    }

    public function showEmployee($id): JsonResponse
    {
        $employee = Employee::with([
            'department', 'designation', 'manager', 'subordinates',
            'salaryStructure', 'documents', 'leaveBalances.leaveType',
            'attendances' => fn($q) => $q->latest()->take(10),
            'payrolls' => fn($q) => $q->latest()->take(6),
        ])->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $employee]);
    }

    public function updateEmployee(Request $request, $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);
        $employee->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Employee updated successfully', 'data' => $employee->fresh(['department', 'designation'])]);
    }

    public function destroyEmployee($id): JsonResponse
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        return response()->json(['status' => 'success', 'message' => 'Employee deleted successfully']);
    }

    // ==========================================
    // DEPARTMENTS & DESIGNATIONS
    // ==========================================

    public function departments(Request $request): JsonResponse
    {
        $departments = Department::with(['manager', 'parent', 'children'])
            ->withCount('employees')
            ->when($this->getCompanyId($request), fn($q, $id) => $q->where('company_id', $id))
            ->get();
        return response()->json(['status' => 'success', 'data' => $departments]);
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'department_code' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $validated['company_id'] = $this->getCompanyId($request);
        $dept = Department::create($validated);
        return response()->json(['status' => 'success', 'message' => 'Department created successfully', 'data' => $dept], 201);
    }

    public function designations(Request $request): JsonResponse
    {
        $designations = Designation::with('department')
            ->withCount('employees')
            ->get();
        return response()->json(['status' => 'success', 'data' => $designations]);
    }

    public function roles(): JsonResponse
    {
        $roles = Role::select('id', 'name', 'display_name')->get();
        return response()->json(['status' => 'success', 'data' => $roles]);
    }

    public function storeDesignation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'code' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
            'description' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $desig = Designation::create($validated);
        return response()->json(['status' => 'success', 'message' => 'Designation created successfully', 'data' => $desig], 201);
    }

    // ==========================================
    // ATTENDANCE
    // ==========================================

    public function attendance(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->listAttendance($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $attendance]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'device' => 'nullable|string',
        ]);

        $record = $this->attendanceService->checkIn($validated['employee_id'], $this->getCompanyId($request), $validated);
        return response()->json(['status' => 'success', 'message' => 'Check-in recorded successfully', 'data' => $record]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'device' => 'nullable|string',
        ]);

        $record = $this->attendanceService->checkOut($validated['employee_id'], $this->getCompanyId($request), $validated);
        return response()->json(['status' => 'success', 'message' => 'Check-out recorded successfully', 'data' => $record]);
    }

    public function markAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'status' => 'required|string|in:present,late,absent,half_day,leave,holiday',
            'check_in' => 'nullable|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s',
            'notes' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $attendance = Attendance::updateOrCreate(
            [
                'company_id' => $companyId,
                'employee_id' => $validated['employee_id'],
                'date' => $validated['date'],
            ],
            $validated
        );

        return response()->json(['status' => 'success', 'message' => 'Attendance marked successfully', 'data' => $attendance]);
    }

    // ==========================================
    // LEAVE
    // ==========================================

    public function leave(Request $request): JsonResponse
    {
        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver']);

        if ($cid = $this->getCompanyId($request)) {
            $query->where('company_id', $cid);
        }

        if ($empId = $request->get('employee_id')) {
            $query->where('employee_id', $empId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by leave type name (partial match on leave_types.name)
        if ($leaveType = $request->get('leave_type')) {
            $query->whereHas('leaveType', function ($q) use ($leaveType) {
                $q->where('name', 'like', "%{$leaveType}%");
            });
        }

        // Filter by date range (start_date)
        if ($dateFrom = $request->get('date_from')) {
            $query->where('start_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->where('end_date', '<=', $dateTo);
        }

        $perPage = min((int) $request->get('per_page', 15), 500);

        return response()->json([
            'status' => 'success',
            'data'   => $query->latest()->paginate($perPage),
        ]);
    }

    public function storeLeave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'required|string|min:3',
            'attachment'    => 'nullable|string',
        ]);

        $leave = $this->leaveService->submitRequest($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'message' => 'Leave request submitted', 'data' => $leave], 201);
    }

    public function approveLeave(Request $request, $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);
        $approved = $this->leaveService->approveRequest($leave, auth()->id());
        return response()->json(['status' => 'success', 'message' => 'Leave request approved and balance updated', 'data' => $approved]);
    }

    public function rejectLeave(Request $request, $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);
        $rejected = $this->leaveService->rejectRequest($leave, $request->get('reason'), auth()->id());
        return response()->json(['status' => 'success', 'message' => 'Leave request rejected', 'data' => $rejected]);
    }

    public function deleteLeave(Request $request, $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);

        // Only allow deletion of pending requests or if admin
        $user = auth()->user();
        $isAdmin = in_array($user->role ?? '', ['Super Admin', 'Admin', 'HR Manager']);

        if (!$isAdmin && $leave->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only pending leave requests can be deleted.',
            ], 403);
        }

        $leave->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Leave request deleted successfully.',
        ]);
    }

    public function leaveTypes(Request $request): JsonResponse
    {
        $types = LeaveType::where(function ($q) use ($request) {
            if ($cid = $this->getCompanyId($request)) {
                $q->where('company_id', $cid)->orWhereNull('company_id');
            }
        })->orderBy('name')->get();

        return response()->json(['status' => 'success', 'data' => $types]);
    }

    public function leaveBalances(Request $request): JsonResponse
    {
        $empId = $request->get('employee_id');
        if (!$empId) {
            $types = LeaveType::all();
            return response()->json(['status' => 'success', 'data' => $types]);
        }
        $balances = $this->leaveService->getEmployeeBalances((int) $empId);
        return response()->json(['status' => 'success', 'data' => $balances]);
    }

    // ==========================================
    // HOLIDAYS
    // ==========================================

    public function holidays(Request $request): JsonResponse
    {
        $holidays = Holiday::when($this->getCompanyId($request), fn($q, $id) => $q->where('company_id', $id))
            ->orderBy('date')
            ->get();
        return response()->json(['status' => 'success', 'data' => $holidays]);
    }

    public function storeHoliday(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'date' => 'required|date',
            'type' => 'nullable|string|in:public,company,optional',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $validated['company_id'] = $this->getCompanyId($request);
        $holiday = Holiday::create($validated);
        return response()->json(['status' => 'success', 'message' => 'Holiday created successfully', 'data' => $holiday], 201);
    }

    // ==========================================
    // PAYROLL
    // ==========================================

    public function payroll(Request $request): JsonResponse
    {
        $query = Payroll::with(['employee.department', 'employee.designation', 'items', 'payslip']);

        if ($cid = $this->getCompanyId($request)) {
            $query->where('company_id', $cid);
        }

        if ($month = $request->get('month')) {
            $query->where('month', $month);
        }

        if ($year = $request->get('year')) {
            $query->where('year', $year);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $periods = PayrollPeriod::when($this->getCompanyId($request), fn($q, $id) => $q->where('company_id', $id))->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'payrolls' => $query->latest('id')->paginate($request->get('per_page', 15)),
                'periods' => $periods,
            ]
        ]);
    }

    public function calculatePayroll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|string',
            'year' => 'required|integer',
        ]);

        $companyId = $this->getCompanyId($request);
        $employee = Employee::findOrFail($validated['employee_id']);

        $startDate = Carbon::parse("1 {$validated['month']} {$validated['year']}")->startOfMonth();
        $endDate = (clone $startDate)->endOfMonth();

        $period = PayrollPeriod::firstOrCreate(
            ['company_id' => $companyId, 'name' => "{$validated['month']} {$validated['year']} Payroll"],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_date' => (clone $endDate)->addDays(5),
                'status' => 'draft',
            ]
        );

        $payroll = $this->payrollService->calculateEmployeePayroll($employee, $period);
        return response()->json(['status' => 'success', 'message' => 'Payroll calculated successfully', 'data' => $payroll]);
    }

    public function payPayroll(Request $request, $id): JsonResponse
    {
        $payroll = Payroll::findOrFail($id);
        $paid = $this->payrollService->payPayroll($payroll, $request->get('account_id'));
        return response()->json(['status' => 'success', 'message' => 'Payroll processed and integrated with Finance module', 'data' => $paid]);
    }

    // ==========================================
    // RECRUITMENT
    // ==========================================

    public function recruitmentJobs(Request $request): JsonResponse
    {
        $jobs = JobPosition::with(['department', 'designation'])
            ->withCount('candidates')
            ->when($this->getCompanyId($request), fn($q, $id) => $q->where('company_id', $id))
            ->get();
        return response()->json(['status' => 'success', 'data' => $jobs]);
    }

    public function storeJob(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'location' => 'nullable|string',
            'employment_type' => 'nullable|string',
            'experience' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'vacancies' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:draft,open,on_hold,closed',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getCompanyId($request);
        $job = JobPosition::create($validated);
        return response()->json(['status' => 'success', 'message' => 'Job position created successfully', 'data' => $job], 201);
    }

    public function recruitmentCandidates(Request $request): JsonResponse
    {
        $pipeline = $this->recruitmentService->getPipeline($this->getCompanyId($request), $request->get('job_position_id'));
        return response()->json(['status' => 'success', 'data' => $pipeline]);
    }

    public function storeCandidate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'job_position_id' => 'nullable|exists:job_positions,id',
            'experience' => 'nullable|string',
            'skills' => 'nullable|string',
            'location' => 'nullable|string',
            'expected_salary' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getCompanyId($request);
        $validated['stage'] = 'applied';
        $validated['applied_date'] = Carbon::today();

        $candidate = Candidate::create($validated);
        return response()->json(['status' => 'success', 'message' => 'Candidate added successfully', 'data' => $candidate], 201);
    }

    public function updateCandidateStage(Request $request, $id): JsonResponse
    {
        $candidate = Candidate::findOrFail($id);
        $newStage = $request->validate(['stage' => 'required|string'])['stage'];
        $updated = $this->recruitmentService->updateCandidateStage($candidate, $newStage);
        return response()->json(['status' => 'success', 'message' => "Candidate moved to {$newStage}", 'data' => $updated]);
    }

    public function scheduleInterview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'interview_type' => 'nullable|string|in:phone,video,in_person,technical,hr',
            'interview_date' => 'required|date',
            'interview_time' => 'required',
            'interviewers' => 'nullable|string',
            'meeting_link' => 'nullable|string',
        ]);

        $interview = $this->recruitmentService->scheduleInterview($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'message' => 'Interview scheduled', 'data' => $interview], 201);
    }

    public function createOffer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'joining_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'benefits' => 'nullable|string',
        ]);

        $offer = $this->recruitmentService->createOffer($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'message' => 'Job offer created and sent', 'data' => $offer], 201);
    }

    // ==========================================
    // PERFORMANCE
    // ==========================================

    public function performance(Request $request): JsonResponse
    {
        $overview = $this->performanceService->getCyclesOverview($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $overview]);
    }

    public function submitReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cycle_id' => 'required|exists:performance_cycles,id',
            'employee_id' => 'required|exists:employees,id',
            'self_rating' => 'nullable|integer|min:1|max:5',
            'self_comments' => 'nullable|string',
            'manager_rating' => 'nullable|integer|min:1|max:5',
            'manager_comments' => 'nullable|string',
        ]);

        $review = $this->performanceService->submitReview($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'message' => 'Performance review submitted', 'data' => $review]);
    }

    // ==========================================
    // TRAINING
    // ==========================================

    public function training(Request $request): JsonResponse
    {
        $programs = $this->trainingService->listPrograms($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $programs]);
    }

    public function storeTraining(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'trainer' => 'nullable|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'duration_hours' => 'nullable|integer',
            'location' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'sessions' => 'nullable|array',
        ]);

        $program = $this->trainingService->createProgram($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'message' => 'Training program created', 'data' => $program], 201);
    }

    public function enrollTraining(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:training_programs,id',
            'employee_id' => 'required|exists:employees,id',
        ]);

        $enrollment = $this->trainingService->enrollEmployee($validated['program_id'], $validated['employee_id']);
        return response()->json(['status' => 'success', 'message' => 'Employee enrolled in training', 'data' => $enrollment], 201);
    }

    // ==========================================
    // ANALYTICS
    // ==========================================

    public function analytics(Request $request): JsonResponse
    {
        $analytics = $this->analyticsService->getAnalytics($this->getCompanyId($request), $request->all());
        return response()->json(['status' => 'success', 'data' => $analytics]);
    }

    // ==========================================
    // UNAVAILABLE USERS (Leave / Inactive / Out-of-office)
    // ==========================================

    public function unavailableUsers(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $search = $request->get('search');
        $statusFilter = $request->get('status'); // all | unavailable | available

        $today = Carbon::today()->toDateString();

        // 1. Get employees with their user account and current leave request
        $employeesQuery = Employee::where('company_id', $companyId)
            ->with(['department', 'designation', 'user'])
            ->with(['leaveRequests' => function ($q) use ($today) {
                $q->where('status', 'approved')
                  ->where('end_date', '>=', $today)
                  ->orderBy('end_date', 'desc');
            }]);

        if ($search) {
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('role', 'like', "%{$search}%"));
            });
        }

        $allEmployees = $employeesQuery->get();

        $records = [];
        $totalUnavailable = 0;
        $totalAvailable = 0;

        foreach ($allEmployees as $emp) {
            $activeLeave = $emp->leaveRequests->first();
            $isLeaveActive = $activeLeave && ($activeLeave->start_date <= $today && $activeLeave->end_date >= $today);
            $isInactive = ($emp->status !== 'active') || ($emp->user && !$emp->user->is_active);

            $isUnavailable = $isLeaveActive || $isInactive || ($emp->status === 'on_leave');

            if ($isUnavailable) {
                $totalUnavailable++;
            } else {
                $totalAvailable++;
            }

            $endDate = null;
            $unavailabilityReason = null;

            if ($isLeaveActive) {
                $endDate = $activeLeave->end_date ? Carbon::parse($activeLeave->end_date)->format('Y-m-d') : null;
                $unavailabilityReason = $activeLeave->reason ?: ($activeLeave->leave_type_id ? 'Approved Leave' : 'On Leave');
            } elseif ($emp->status === 'on_leave') {
                $endDate = $activeLeave?->end_date ? Carbon::parse($activeLeave->end_date)->format('Y-m-d') : 'TBD';
                $unavailabilityReason = 'On Official Leave';
            } elseif ($isInactive) {
                $endDate = 'Indefinite';
                $unavailabilityReason = 'Account Deactivated / Inactive';
            }

            $item = [
                'id' => $emp->id,
                'user_id' => $emp->user_id,
                'name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: ($emp->user?->name ?? 'Unknown'),
                'email' => $emp->email ?: ($emp->user?->email ?? ''),
                'employee_code' => $emp->employee_code,
                'role' => $emp->user?->role ?? ($emp->designation?->name ?? 'Employee'),
                'department' => $emp->department?->name ?? 'General',
                'avatar' => $emp->avatar ?: ($emp->user?->avatar ?? null),
                'status' => $isUnavailable ? 'Unavailable' : 'Available',
                'reason' => $unavailabilityReason,
                'end_date' => $endDate,
                'leave_details' => $activeLeave ? [
                    'start_date' => $activeLeave->start_date,
                    'end_date' => $activeLeave->end_date,
                    'days_count' => $activeLeave->days_count,
                    'reason' => $activeLeave->reason,
                ] : null,
            ];

            // Apply filter
            if ($statusFilter === 'unavailable' && !$isUnavailable) {
                continue;
            }
            if ($statusFilter === 'available' && $isUnavailable) {
                continue;
            }

            $records[] = $item;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'records' => $records,
                'metrics' => [
                    'total_count' => count($allEmployees),
                    'total_unavailable' => $totalUnavailable,
                    'total_available' => $totalAvailable,
                ],
            ],
        ]);
    }
}
