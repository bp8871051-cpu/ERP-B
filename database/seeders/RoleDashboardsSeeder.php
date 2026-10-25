<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\Milestone;
use App\Models\Ticket;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\ServiceSiteVisit;
use App\Models\ServiceReport;
use App\Models\QcInspection;
use App\Models\QcNcr;
use App\Models\ProjectSiteReport;
use App\Models\ProjectEngineeringIssue;
use App\Models\ProjectTechnicalReview;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RoleDashboardsSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;
        $now = Carbon::now();

        // 1. ROLES & PERMISSIONS SETUP
        $rolesData = [
            'Service Engineer' => [
                'display_name' => 'Service Engineer',
                'description' => 'Field service, maintenance schedules, site visits, service tickets and spare parts requests',
            ],
            'Store Executive' => [
                'display_name' => 'Store Executive',
                'description' => 'Stock receipts, material issue, stock movements, bin/warehouse management and physical inventory',
            ],
            'Quality Control' => [
                'display_name' => 'Quality Control',
                'description' => 'Inspection requests, material testing, quality hold, defect reporting and non-conformance records (NCR)',
            ],
            'Project Engineer' => [
                'display_name' => 'Project Engineer',
                'description' => 'Site execution, task milestones, engineering issues, progress reports and project material requests',
            ],
        ];

        $createdRoles = [];
        foreach ($rolesData as $name => $data) {
            $createdRoles[$name] = Role::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $data['display_name'],
                    'description' => $data['description'],
                ]
            );
        }

        // Create specific permissions
        $newPerms = [
            // Service
            ['name' => 'service.view', 'module' => 'Service', 'description' => 'View service tickets and maintenance'],
            ['name' => 'service.create', 'module' => 'Service', 'description' => 'Create service reports and visits'],
            ['name' => 'service.edit', 'module' => 'Service', 'description' => 'Update service jobs and schedules'],
            ['name' => 'service.schedule_visit', 'module' => 'Service', 'description' => 'Schedule customer site visits'],
            ['name' => 'service.submit_report', 'module' => 'Service', 'description' => 'Submit field service reports'],
            ['name' => 'service.request_parts', 'module' => 'Service', 'description' => 'Request spare parts from store'],

            // Store
            ['name' => 'store.view', 'module' => 'Store', 'description' => 'View store inventory and movements'],
            ['name' => 'store.inward', 'module' => 'Store', 'description' => 'Create stock inward entries'],
            ['name' => 'store.outward', 'module' => 'Store', 'description' => 'Create stock outward entries'],
            ['name' => 'store.issue_material', 'module' => 'Store', 'description' => 'Issue materials against approved requests'],
            ['name' => 'store.manage_stock', 'module' => 'Store', 'description' => 'Manage physical bin stock and adjustments'],

            // QC
            ['name' => 'qc.view', 'module' => 'QC', 'description' => 'View QC inspection queue and records'],
            ['name' => 'qc.inspect', 'module' => 'QC', 'description' => 'Conduct quality inspections and record tests'],
            ['name' => 'qc.record_decision', 'module' => 'QC', 'description' => 'Authorize pass, fail or quality hold decisions'],
            ['name' => 'qc.create_ncr', 'module' => 'QC', 'description' => 'File non-conformance and defect reports'],
            ['name' => 'qc.quality_hold', 'module' => 'QC', 'description' => 'Quarantine and hold defective stock'],

            // Projects
            ['name' => 'projects.manage_tasks', 'module' => 'Projects', 'description' => 'Manage and progress assigned engineering tasks'],
        ];

        foreach ($newPerms as $p) {
            Permission::firstOrCreate(['name' => $p['name']], $p);
        }

        // Sync Super Admin & Admin with all permissions
        $allPermIds = Permission::pluck('id')->toArray();
        Role::where('name', 'Super Admin')->first()?->permissions()->sync($allPermIds);
        Role::where('name', 'Admin')->first()?->permissions()->sync($allPermIds);

        // Assign specific permissions to the 4 roles
        $servicePermIds = Permission::whereIn('name', [
            'dashboard.view', 'service.view', 'service.create', 'service.edit', 
            'service.schedule_visit', 'service.submit_report', 'service.request_parts',
            'support.view_tickets', 'support.reply_tickets', 'inventory.view'
        ])->pluck('id')->toArray();
        $createdRoles['Service Engineer']->permissions()->sync($servicePermIds);

        $storePermIds = Permission::whereIn('name', [
            'dashboard.view', 'store.view', 'store.inward', 'store.outward', 
            'store.issue_material', 'store.manage_stock', 'inventory.view', 
            'inventory.create', 'inventory.manage_stock', 'purchase.view'
        ])->pluck('id')->toArray();
        $createdRoles['Store Executive']->permissions()->sync($storePermIds);

        $qcPermIds = Permission::whereIn('name', [
            'dashboard.view', 'qc.view', 'qc.inspect', 'qc.record_decision', 
            'qc.create_ncr', 'qc.quality_hold', 'inventory.view', 'purchase.view'
        ])->pluck('id')->toArray();
        $createdRoles['Quality Control']->permissions()->sync($qcPermIds);

        $projectEngPermIds = Permission::whereIn('name', [
            'dashboard.view', 'projects.view', 'projects.edit', 'projects.manage_tasks',
            'documents.view', 'documents.upload', 'inventory.view', 'service.request_parts'
        ])->pluck('id')->toArray();
        $createdRoles['Project Engineer']->permissions()->sync($projectEngPermIds);

        // 2. USER ACCOUNTS SETUP
        $deptSupport = Department::firstOrCreate(['name' => 'Customer Support & Field Service'], ['company_id' => $companyId]);
        $deptStore = Department::firstOrCreate(['name' => 'Store & Warehouse Operations'], ['company_id' => $companyId]);
        $deptQC = Department::firstOrCreate(['name' => 'Quality Assurance & Control'], ['company_id' => $companyId]);
        $deptEng = Department::firstOrCreate(['name' => 'Engineering & Projects'], ['company_id' => $companyId]);

        $usersInfo = [
            [
                'email' => 'service@falconerp.com',
                'name' => 'Rajesh Sharma',
                'role' => 'Service Engineer',
                'phone' => '+91 98250 11221',
                'dept_id' => $deptSupport->id,
                'designation' => 'Senior Service Engineer',
                'code' => 'EMP-SRV-001',
            ],
            [
                'email' => 'store@falconerp.com',
                'name' => 'Vikram Patel',
                'role' => 'Store Executive',
                'phone' => '+91 98250 22332',
                'dept_id' => $deptStore->id,
                'designation' => 'Lead Store Executive',
                'code' => 'EMP-STR-001',
            ],
            [
                'email' => 'qc@falconerp.com',
                'name' => 'Ananya Desai',
                'role' => 'Quality Control',
                'phone' => '+91 98250 33443',
                'dept_id' => $deptQC->id,
                'designation' => 'Quality Control Lead',
                'code' => 'EMP-QC-001',
            ],
            [
                'email' => 'projecteng@falconerp.com',
                'name' => 'Amit Verma',
                'role' => 'Project Engineer',
                'phone' => '+91 98250 44554',
                'dept_id' => $deptEng->id,
                'designation' => 'Site Project Engineer',
                'code' => 'EMP-PRJ-001',
            ],
        ];

        $seededUsers = [];
        foreach ($usersInfo as $uInfo) {
            $user = User::firstOrCreate(
                ['email' => $uInfo['email']],
                [
                    'name' => $uInfo['name'],
                    'password' => Hash::make('password'),
                    'role' => $uInfo['role'],
                    'phone' => $uInfo['phone'],
                    'department_id' => $uInfo['dept_id'],
                    'company_id' => $companyId,
                    'is_active' => true,
                ]
            );

            // Ensure role is accurate if already created
            if ($user->role !== $uInfo['role']) {
                $user->update(['role' => $uInfo['role']]);
            }

            // Employee record
            $nameParts = explode(' ', $uInfo['name']);
            Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $companyId,
                    'department_id' => $uInfo['dept_id'],
                    'employee_code' => $uInfo['code'],
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? 'Staff',
                    'email' => $uInfo['email'],
                    'phone' => $uInfo['phone'],
                    'status' => 'active',
                    'joining_date' => Carbon::now()->subMonths(8)->format('Y-m-d'),
                ]
            );

            $seededUsers[$uInfo['role']] = $user;
        }

        // Retrieve existing records to link with
        $product1 = Product::first() ?? Product::create([
            'company_id' => $companyId,
            'name' => 'Industrial Hydraulic Pump Seal 45mm',
            'sku' => 'IND-SEAL-45',
            'price' => 1250.00,
            'cost' => 800.00,
            'quantity' => 45,
            'alert_quantity' => 15,
        ]);

        $product2 = Product::skip(1)->first() ?? Product::create([
            'company_id' => $companyId,
            'name' => 'High-Torque AC Servo Motor 2.5kW',
            'sku' => 'MTR-SRV-25',
            'price' => 18500.00,
            'cost' => 14200.00,
            'quantity' => 6,
            'alert_quantity' => 10,
        ]);

        $product3 = Product::skip(2)->first() ?? Product::create([
            'company_id' => $companyId,
            'name' => 'Stainless Steel Valve Flange DN50',
            'sku' => 'FLG-SS-50',
            'price' => 3400.00,
            'cost' => 2600.00,
            'quantity' => 12,
            'alert_quantity' => 8,
        ]);

        $warehouse = Warehouse::first() ?? Warehouse::create([
            'company_id' => $companyId,
            'name' => 'Central Engineering Warehouse A',
            'code' => 'WH-ENG-01',
            'address' => 'GIDC Industrial Estate, Sector 4, Vadodara, Gujarat',
        ]);

        $customer = Customer::first() ?? Customer::create([
            'company_id' => $companyId,
            'name' => 'Reliance Petrochem Industries Ltd',
            'email' => 'procurement@reliancepetro.example.com',
            'phone' => '+91 98200 45678',
            'city' => 'Surat',
        ]);

        $project = Project::first() ?? Project::create([
            'company_id' => $companyId,
            'name' => 'Phase 2 Petrochem Pipeline Automation',
            'code' => 'PRJ-2026-001',
            'status' => 'in_progress',
            'client_name' => 'Reliance Petrochem',
            'start_date' => $now->copy()->subMonths(2),
            'end_date' => $now->copy()->addMonths(4),
            'budget' => 7500000,
        ]);

        // 3. SEED DATA FOR SERVICE ENGINEER
        $serviceUser = $seededUsers['Service Engineer'];

        // Service Tickets
        $ticket1 = Ticket::firstOrCreate(
            ['ticket_number' => 'SRV-TCK-2026-001'],
            [
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'assigned_agent_id' => $serviceUser->id,
                'department_id' => $deptSupport->id,
                'subject' => 'Main Pump Hydraulic Pressure Drop at Unit 3',
                'description' => 'Unit 3 reported critical pressure degradation under 120 bar load. Requires on-site inspection.',
                'priority' => 'high',
                'status' => 'in_progress',
                'resolution_due_at' => $now->copy()->addDays(1),
            ]
        );

        $ticket2 = Ticket::firstOrCreate(
            ['ticket_number' => 'SRV-TCK-2026-002'],
            [
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'assigned_agent_id' => $serviceUser->id,
                'department_id' => $deptSupport->id,
                'subject' => 'Quarterly Preventive Servicing of Compressor Bank',
                'description' => 'Scheduled quarterly lubrication, filter change, and alignment check.',
                'priority' => 'medium',
                'status' => 'open',
                'resolution_due_at' => $now->copy()->addDays(3),
            ]
        );

        $ticket3 = Ticket::firstOrCreate(
            ['ticket_number' => 'SRV-TCK-2026-003'],
            [
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'assigned_agent_id' => $serviceUser->id,
                'department_id' => $deptSupport->id,
                'subject' => 'Overhaul of Line 2 Coolant Circulation Loop',
                'description' => 'Complete overhaul of Line 2 pump, seal replacement and pressure test successfully performed.',
                'priority' => 'urgent',
                'status' => 'completed',
                'resolved_at' => $now->copy()->subDays(2),
            ]
        );

        // Site Visits
        ServiceSiteVisit::firstOrCreate(
            ['engineer_id' => $serviceUser->id, 'visit_date' => $now->format('Y-m-d')],
            [
                'company_id' => $companyId,
                'ticket_id' => $ticket1->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'site_address' => 'Plant Bay 4, Hazira Petrochemical Complex, Surat',
                'contact_phone' => '+91 98200 45678',
                'visit_time' => '11:30 AM',
                'service_type' => 'breakdown_repair',
                'status' => 'scheduled',
                'notes' => 'Bring calibrated digital pressure gauge and 45mm seal replacement kit.',
            ]
        );

        ServiceSiteVisit::firstOrCreate(
            ['engineer_id' => $serviceUser->id, 'visit_date' => $now->copy()->addDays(2)->format('Y-m-d')],
            [
                'company_id' => $companyId,
                'ticket_id' => $ticket2->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'site_address' => 'Plant Bay 1, Compressor Room, Surat',
                'contact_phone' => '+91 98200 45678',
                'visit_time' => '02:00 PM',
                'service_type' => 'preventive_maintenance',
                'status' => 'scheduled',
                'notes' => 'Check vibration sensors and oil temperature logs.',
            ]
        );

        // Service Reports
        ServiceReport::firstOrCreate(
            ['report_number' => 'SR-2026-001'],
            [
                'company_id' => $companyId,
                'ticket_id' => $ticket3->id,
                'engineer_id' => $serviceUser->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'equipment_name' => 'Centrifugal Booster Pump #4',
                'serial_number' => 'SN-CBP-8841',
                'work_performed' => 'Dismantled pump casing, inspected impeller, replaced worn mechanical seal with OEM seal, purged air and ran load test at 150 bar for 45 mins without leakage.',
                'hours_spent' => 4.5,
                'parts_used' => [
                    ['product_id' => $product1->id, 'name' => $product1->name, 'qty' => 1]
                ],
                'recommendations' => 'Schedule next lubrication inspection in 90 days. Monitor bearing vibration baseline.',
                'customer_confirmation_status' => 'signed_off',
                'sign_off_notes' => 'Customer plant engineer verified vibration specs and signed off on field tablet.',
                'completed_at' => $now->copy()->subDays(2),
            ]
        );

        // Pending Customer Confirmation Service Report
        ServiceReport::firstOrCreate(
            ['report_number' => 'SR-2026-002'],
            [
                'company_id' => $companyId,
                'ticket_id' => $ticket1->id,
                'engineer_id' => $serviceUser->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'equipment_name' => 'Hydraulic Press Ram Cylinder',
                'serial_number' => 'SN-HPR-1029',
                'work_performed' => 'Preliminary hydraulic leak check conducted. Re-torqued valve fittings. Awaiting client site supervisor countersignature.',
                'hours_spent' => 2.0,
                'parts_used' => [],
                'recommendations' => 'Replace inlet o-ring seal during scheduled downtime.',
                'customer_confirmation_status' => 'pending',
                'completed_at' => $now->copy()->subHours(4),
            ]
        );

        // Service Engineer Spare Parts Request
        $srvMatReq = MaterialRequest::firstOrCreate(
            ['request_number' => 'MR-SRV-2026-001'],
            [
                'company_id' => $companyId,
                'requested_by' => $serviceUser->id,
                'request_type' => 'spare_parts',
                'ticket_id' => $ticket1->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'pending',
                'priority' => 'high',
                'required_date' => $now->copy()->addDay()->format('Y-m-d'),
                'site_location' => 'Client Site - Surat Hazira',
                'notes' => 'Urgent replacement seals for high-pressure hydraulic overhaul.',
            ]
        );

        MaterialRequestItem::firstOrCreate(
            ['material_request_id' => $srvMatReq->id, 'product_id' => $product1->id],
            [
                'quantity_requested' => 2,
                'quantity_issued' => 0,
                'unit' => 'pcs',
                'notes' => '45mm Viton High-Temp Seals',
            ]
        );

        // 4. SEED DATA FOR STORE EXECUTIVE
        $storeUser = $seededUsers['Store Executive'];

        // Seed stock movements
        StockMovement::firstOrCreate(
            ['reference' => 'INW-PO-2026-089'],
            [
                'company_id' => $companyId,
                'product_id' => $product1->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'movement_type' => 'purchase',
                'quantity' => 50,
                'quantity_in' => 50,
                'quantity_out' => 0,
                'balance_quantity' => 95,
                'unit_cost' => 800.00,
                'total_cost' => 40000.00,
                'notes' => 'Received from Apex Seals & Gaskets under PO-8821.',
                'created_by' => $storeUser->id,
                'created_at' => $now->copy()->subHours(3),
            ]
        );

        StockMovement::firstOrCreate(
            ['reference' => 'OUT-ISSUE-2026-034'],
            [
                'company_id' => $companyId,
                'product_id' => $product3->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'out',
                'movement_type' => 'out',
                'quantity' => 4,
                'quantity_in' => 0,
                'quantity_out' => 4,
                'balance_quantity' => 12,
                'unit_cost' => 2600.00,
                'total_cost' => 10400.00,
                'notes' => 'Issued to Project Engineering site team for Bay 2 installation.',
                'created_by' => $storeUser->id,
                'created_at' => $now->copy()->subHours(5),
            ]
        );

        // Stock Adjustment
        StockAdjustment::firstOrCreate(
            ['reference_number' => 'ADJ-2026-004'],
            [
                'company_id' => $companyId,
                'warehouse_id' => $warehouse->id,
                'adjustment_type' => 'decrease',
                'status' => 'pending',
                'reason' => 'Physical cycle count difference found during monthly Bin-B audit.',
                'created_by' => $storeUser->id,
                'notes' => 'Pending manager approval for discrepancy.',
            ]
        );

        // 5. SEED DATA FOR QUALITY CONTROL
        $qcUser = $seededUsers['Quality Control'];

        // Pending QC Inspection
        QcInspection::firstOrCreate(
            ['inspection_number' => 'QC-INSP-2026-001'],
            [
                'company_id' => $companyId,
                'product_id' => $product2->id,
                'warehouse_id' => $warehouse->id,
                'inspector_id' => $qcUser->id,
                'lot_number' => 'LOT-2026-MTR-09',
                'inspection_type' => 'incoming_material',
                'batch_quantity' => 25,
                'sample_size' => 5,
                'status' => 'pending',
                'defect_count' => 0,
                'notes' => 'Incoming shipment from Siemens Power Technologies. Requires insulation resistance and shaft run-out checks.',
            ]
        );

        // In-Progress QC Inspection
        QcInspection::firstOrCreate(
            ['inspection_number' => 'QC-INSP-2026-002'],
            [
                'company_id' => $companyId,
                'product_id' => $product3->id,
                'warehouse_id' => $warehouse->id,
                'inspector_id' => $qcUser->id,
                'lot_number' => 'LOT-2026-FLG-44',
                'inspection_type' => 'incoming_material',
                'batch_quantity' => 60,
                'sample_size' => 8,
                'status' => 'in_progress',
                'defect_count' => 0,
                'measurements' => [
                    ['parameter' => 'Bore Diameter', 'standard' => '50.00 ±0.05 mm', 'actual' => '50.02 mm', 'status' => 'pass'],
                    ['parameter' => 'Flange Thickness', 'standard' => '16.00 ±0.10 mm', 'actual' => '16.05 mm', 'status' => 'pass'],
                    ['parameter' => 'Bolt Hole Pitch', 'standard' => '125.00 ±0.20 mm', 'actual' => '125.10 mm', 'status' => 'pass'],
                ],
                'checklist' => [
                    ['check' => 'Surface finish free of pits and burrs', 'checked' => true],
                    ['check' => 'Material test certificate EN 10204 3.1 attached', 'checked' => true],
                    ['check' => 'Hydrostatic pressure test at 25 bar', 'checked' => false],
                ],
                'notes' => 'Dimensional checks verified. Conducting pressure holding test.',
            ]
        );

        // Passed QC Inspection
        QcInspection::firstOrCreate(
            ['inspection_number' => 'QC-INSP-2026-003'],
            [
                'company_id' => $companyId,
                'product_id' => $product1->id,
                'warehouse_id' => $warehouse->id,
                'inspector_id' => $qcUser->id,
                'lot_number' => 'LOT-2026-SEAL-88',
                'inspection_type' => 'incoming_material',
                'batch_quantity' => 100,
                'sample_size' => 12,
                'status' => 'passed',
                'decision_date' => $now->copy()->subDays(1),
                'defect_count' => 0,
                'measurements' => [
                    ['parameter' => 'Durometer Hardness', 'standard' => '75 ±5 Shore A', 'actual' => '76 Shore A', 'status' => 'pass'],
                    ['parameter' => 'Inner Ring Diameter', 'standard' => '45.00 ±0.02 mm', 'actual' => '45.01 mm', 'status' => 'pass'],
                ],
                'notes' => '100% compliant with high-temperature viton grade specs. Released to warehouse active bin.',
            ]
        );

        // Quality Hold & Defect Inspection
        $failedInsp = QcInspection::firstOrCreate(
            ['inspection_number' => 'QC-INSP-2026-004'],
            [
                'company_id' => $companyId,
                'product_id' => $product2->id,
                'warehouse_id' => $warehouse->id,
                'inspector_id' => $qcUser->id,
                'lot_number' => 'LOT-2026-MTR-HOLD',
                'inspection_type' => 'incoming_material',
                'batch_quantity' => 10,
                'sample_size' => 3,
                'status' => 'quality_hold',
                'decision_date' => $now->copy()->subHours(12),
                'defect_count' => 2,
                'notes' => 'Shaft radial run-out exceeded tolerance limits (0.08mm measured vs 0.03mm max allowable). Quarantined in QC Hold Bay.',
                'reinspection_date' => $now->copy()->addDays(5)->format('Y-m-d'),
            ]
        );

        // Open NCR for the failed inspection
        QcNcr::firstOrCreate(
            ['ncr_number' => 'NCR-2026-001'],
            [
                'company_id' => $companyId,
                'inspection_id' => $failedInsp->id,
                'product_id' => $product2->id,
                'reported_by' => $qcUser->id,
                'title' => 'Excessive Shaft Radial Runout in 2.5kW Servo Motors',
                'description' => '2 units out of 3 sampled exhibited shaft eccentricity exceeding DIN 42955 precision grade tolerance.',
                'severity' => 'major',
                'status' => 'capa_pending',
                'root_cause' => 'Supplier lathe centering clamp slippage during final machining.',
                'corrective_action' => 'Vendor notified for root-cause audit; batch placed on hold pending vendor RMA inspection.',
                'action_assigned_to' => $qcUser->id,
                'action_due_date' => $now->copy()->addDays(7)->format('Y-m-d'),
            ]
        );

        // 6. SEED DATA FOR PROJECT ENGINEER
        $projUser = $seededUsers['Project Engineer'];

        // Link project engineer to project
        \App\Models\ProjectMember::firstOrCreate(
            ['project_id' => $project->id, 'user_id' => $projUser->id],
            ['role' => 'Project Engineer']
        );

        // Tasks assigned to project engineer
        $task1 = Task::firstOrCreate(
            ['title' => 'Install and Calibrate Flow Transmitters at Valve Station 3'],
            [
                'company_id' => $companyId,
                'project_id' => $project->id,
                'assigned_to' => $projUser->id,
                'description' => 'Mount differential pressure flow transmitters, pull armored signal cables, and calibrate 4-20mA loop to DCS.',
                'priority' => 'high',
                'status' => 'in_progress',
                'start_date' => $now->copy()->subDays(3)->format('Y-m-d'),
                'due_date' => $now->copy()->addDays(2)->format('Y-m-d'),
            ]
        );

        $task2 = Task::firstOrCreate(
            ['title' => 'Hydrostatic Pressure Test of Sector 2 Weld Joints'],
            [
                'company_id' => $companyId,
                'project_id' => $project->id,
                'assigned_to' => $projUser->id,
                'description' => 'Pressurize Section 2 pipeline to 45 bar holding for 4 hours with third-party TPI witness.',
                'priority' => 'urgent',
                'status' => 'pending',
                'start_date' => $now->copy()->addDays(1)->format('Y-m-d'),
                'due_date' => $now->copy()->addDays(4)->format('Y-m-d'),
            ]
        );

        $task3 = Task::firstOrCreate(
            ['title' => 'Review and Stamp As-Built Isometric Piping Drawings'],
            [
                'company_id' => $companyId,
                'project_id' => $project->id,
                'assigned_to' => $projUser->id,
                'description' => 'Validate field modifications against design PID and generate redline markup.',
                'priority' => 'medium',
                'status' => 'pending',
                'start_date' => $now->copy()->subDays(5)->format('Y-m-d'),
                'due_date' => $now->copy()->subDay()->format('Y-m-d'), // Overdue!
            ]
        );

        // Upcoming Milestone
        Milestone::firstOrCreate(
            ['title' => 'Mechanical Completion & Cold Commissioning', 'project_id' => $project->id],
            [
                'due_date' => $now->copy()->addDays(14)->format('Y-m-d'),
                'status' => 'pending',
            ]
        );

        // Daily Site Progress Report
        ProjectSiteReport::firstOrCreate(
            ['project_id' => $project->id, 'report_date' => $now->format('Y-m-d')],
            [
                'company_id' => $companyId,
                'engineer_id' => $projUser->id,
                'work_completed' => 'Completed pipeline alignment on Header 3B. Torqued 48 flange bolts to 320 Nm using hydraulic wrench. Positioned cable trays along gantry section 4.',
                'site_conditions' => 'Clear weather, 31°C, site safe and compliant with OSHA PPE standards.',
                'blockers' => 'Awaiting Store Executive issue of 4 additional DN50 SS flanges for branch connection.',
                'manpower_count' => 14,
            ]
        );

        // Engineering Issue
        ProjectEngineeringIssue::firstOrCreate(
            ['title' => 'Interference Between Cable Tray Route and Steam Trace Piping'],
            [
                'company_id' => $companyId,
                'project_id' => $project->id,
                'engineer_id' => $projUser->id,
                'description' => 'Field clash detected at elevation +4.2m between electrical instrument tray and 2" steam trace pipe.',
                'severity' => 'high',
                'status' => 'open',
                'mitigation_plan' => 'Offset instrument tray by 300mm to north using cantilever brackets; technical review document submitted.',
            ]
        );

        // Technical Review
        ProjectTechnicalReview::firstOrCreate(
            ['title' => 'Elevation +4.2m Tray Reroute & Stress Calculation'],
            [
                'company_id' => $companyId,
                'project_id' => $project->id,
                'submitted_by' => $projUser->id,
                'deliverable_type' => 'drawing',
                'status' => 'under_review',
                'version' => '1.2',
                'submission_notes' => 'Modified isometric routing drawing resolving interference with steam line.',
                'review_comments' => 'Structural engineer reviewing load calculation on cantilever support.',
            ]
        );

        // Project Engineer Material Request to Store
        $prjMatReq = MaterialRequest::firstOrCreate(
            ['request_number' => 'MR-PRJ-2026-001'],
            [
                'company_id' => $companyId,
                'requested_by' => $projUser->id,
                'request_type' => 'project_material',
                'project_id' => $project->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'pending',
                'priority' => 'urgent',
                'required_date' => $now->copy()->addDay()->format('Y-m-d'),
                'site_location' => 'Pipeline Automation Bay 2',
                'notes' => 'Flanges required to close line before hydro-test tomorrow.',
            ]
        );

        MaterialRequestItem::firstOrCreate(
            ['material_request_id' => $prjMatReq->id, 'product_id' => $product3->id],
            [
                'quantity_requested' => 4,
                'quantity_issued' => 0,
                'unit' => 'pcs',
                'notes' => 'Stainless Steel Valve Flange DN50 Grade 316',
            ]
        );
    }
}
