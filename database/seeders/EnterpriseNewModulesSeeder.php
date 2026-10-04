<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetAssignmentHistory;
use App\Models\AssetCategory;
use App\Models\AssetDepreciation;
use App\Models\AssetDepreciationSchedule;
use App\Models\AssetDisposal;
use App\Models\AssetLocation;
use App\Models\AssetMaintenance;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentCategory;
use App\Models\DocumentCompliance;
use App\Models\DocumentFolder;
use App\Models\DocumentPolicy;
use App\Models\DocumentVersion;
use App\Models\DocumentWorkflow;
use App\Models\DocumentWorkflowStep;
use App\Models\Employee;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosPayment;
use App\Models\PosPrintSetting;
use App\Models\PosRefund;
use App\Models\PosRefundItem;
use App\Models\PosRegister;
use App\Models\PosRegisterSession;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnterpriseNewModulesSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        if ($companies->isEmpty()) {
            $company = Company::create([
                'name' => 'Falcon Enterprise Ltd',
                'email' => 'info@falconerp.com',
                'phone' => '+91 98765 43210',
                'tax_number' => '27AAACG0123M1Z9',
                'address' => 'Corporate Tower, Level 4, Tech City',
                'currency' => 'INR',
            ]);
            $companies = collect([$company]);
        }

        foreach ($companies as $comp) {
            $this->seedPosModule($comp);
            $this->seedAssetsModule($comp);
            $this->seedDocumentsModule($comp);
        }
    }

    protected function seedPosModule(Company $company): void
    {
        // 1. POS Print Settings
        PosPrintSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'receipt_width' => '80mm',
                'show_logo' => true,
                'show_company_details' => true,
                'show_gst' => true,
                'show_sku' => true,
                'show_barcode' => true,
                'show_qr' => true,
                'footer_text' => 'Goods once sold will only be exchanged as per store policy within 7 days.',
                'thank_you_message' => 'Thank you for shopping with us! Have a wonderful day.',
                'auto_print' => false,
                'print_copies' => 1,
            ]
        );

        // 2. POS Registers
        $reg1 = PosRegister::firstOrCreate(
            ['company_id' => $company->id, 'register_code' => 'REG-01-MAIN'],
            ['name' => 'Counter 1 - Express Register', 'status' => 'open', 'is_active' => true]
        );

        $reg2 = PosRegister::firstOrCreate(
            ['company_id' => $company->id, 'register_code' => 'REG-02-RETAIL'],
            ['name' => 'Counter 2 - Main Retail POS', 'status' => 'closed', 'is_active' => true]
        );

        $cashier = User::where('company_id', $company->id)->first() ?: User::first();
        if (!$cashier) return;

        // 3. Active Register Session
        $session = PosRegisterSession::firstOrCreate(
            [
                'company_id' => $company->id,
                'pos_register_id' => $reg1->id,
                'status' => 'open',
            ],
            [
                'cashier_id' => $cashier->id,
                'opened_at' => Carbon::today()->setHour(9)->setMinute(0),
                'opening_cash' => 1000.00,
                'total_sales' => 5400.00,
                'total_transactions' => 6,
                'notes' => 'Morning shift active',
            ]
        );

        // 4. Seed sample POS Orders if few exist
        if (PosOrder::where('company_id', $company->id)->count() < 5) {
            $customer = Customer::where('company_id', $company->id)->first() ?: Customer::first();
            $products = Product::where('company_id', $company->id)->take(4)->get();
            if ($products->isEmpty()) {
                $products = Product::take(4)->get();
            }

            if ($products->isNotEmpty()) {
                for ($i = 1; $i <= 6; $i++) {
                    $orderNum = sprintf('POS-2026-%06d', 1000 + $i);
                    $prod = $products->random();
                    $qty = rand(1, 3);
                    $price = (float) $prod->selling_price ?: 450.00;
                    $subtotal = $price * $qty;
                    $tax = round($subtotal * 0.18, 2);
                    $total = $subtotal + $tax;

                    $method = ['cash', 'card', 'upi'][$i % 3];

                    $order = PosOrder::create([
                        'company_id' => $company->id,
                        'warehouse_id' => 1,
                        'register_session_id' => $session->id,
                        'cashier_id' => $cashier->id,
                        'customer_id' => $customer?->id,
                        'order_number' => $orderNum,
                        'subtotal' => $subtotal,
                        'discount_amount' => 0,
                        'round_off' => 0,
                        'tax_amount' => $tax,
                        'total_amount' => $total,
                        'paid_amount' => $total,
                        'change_amount' => 0,
                        'payment_method' => $method,
                        'payment_status' => 'paid',
                        'order_status' => 'completed',
                        'status' => 'completed',
                        'created_at' => Carbon::today()->subHours(rand(1, 8)),
                    ]);

                    PosOrderItem::create([
                        'pos_order_id' => $order->id,
                        'product_id' => $prod->id,
                        'product_name' => $prod->name,
                        'sku' => $prod->sku,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'tax_amount' => $tax,
                        'subtotal' => $subtotal,
                        'total_price' => $total,
                    ]);

                    PosPayment::create([
                        'company_id' => $company->id,
                        'pos_order_id' => $order->id,
                        'payment_method' => $method,
                        'amount' => $total,
                        'currency' => 'INR',
                        'reference_no' => 'PAY-' . strtoupper(Str::random(8)),
                        'status' => 'completed',
                    ]);
                }
            }
        }
    }

    protected function seedAssetsModule(Company $company): void
    {
        // 1. Asset Categories
        $categories = [
            ['name' => 'IT Hardware & Laptops', 'code' => 'CAT-IT', 'depreciation_method' => 'straight_line', 'useful_life_years' => 3, 'salvage_percentage' => 5],
            ['name' => 'Office Furniture & Fixtures', 'code' => 'CAT-FURN', 'depreciation_method' => 'declining_balance', 'useful_life_years' => 7, 'salvage_percentage' => 10],
            ['name' => 'Machinery & Tools', 'code' => 'CAT-MACH', 'depreciation_method' => 'double_declining', 'useful_life_years' => 10, 'salvage_percentage' => 8],
            ['name' => 'Company Vehicles', 'code' => 'CAT-VEH', 'depreciation_method' => 'straight_line', 'useful_life_years' => 5, 'salvage_percentage' => 15],
            ['name' => 'Telecom & Network Gear', 'code' => 'CAT-TEL', 'depreciation_method' => 'straight_line', 'useful_life_years' => 4, 'salvage_percentage' => 5],
        ];

        $createdCats = [];
        foreach ($categories as $cat) {
            $createdCats[] = AssetCategory::firstOrCreate(
                ['company_id' => $company->id, 'code' => $cat['code']],
                array_merge($cat, ['company_id' => $company->id])
            );
        }

        // 2. Asset Locations
        $locations = [
            ['name' => 'Headquarters - Floor 3 (Tech Hub)', 'building' => 'Building A', 'floor' => 'Floor 3', 'room' => 'Lab 301'],
            ['name' => 'Headquarters - Floor 2 (Executive)', 'building' => 'Building A', 'floor' => 'Floor 2', 'room' => 'Suite 204'],
            ['name' => 'Central Warehouse & Logistics', 'building' => 'Logistics Park', 'floor' => 'Ground', 'room' => 'Bay 4'],
            ['name' => 'Branch Office - West Region', 'building' => 'Commerce Plaza', 'floor' => 'Floor 5', 'room' => 'Unit 502'],
        ];

        $createdLocs = [];
        foreach ($locations as $loc) {
            $createdLocs[] = AssetLocation::firstOrCreate(
                ['company_id' => $company->id, 'name' => $loc['name']],
                array_merge($loc, ['company_id' => $company->id])
            );
        }

        // 3. Departments & Employees
        $department = Department::where('company_id', $company->id)->first() ?: Department::first();
        $employee = Employee::where('company_id', $company->id)->first() ?: Employee::first();

        // 4. Sample Assets Register
        if (Asset::where('company_id', $company->id)->count() < 6) {
            $assetData = [
                [
                    'name' => 'MacBook Pro 16" M3 Max 64GB',
                    'category_id' => $createdCats[0]->id,
                    'serial_number' => 'C02G8792MD6R',
                    'model_number' => 'A2991',
                    'brand' => 'Apple',
                    'purchase_date' => Carbon::now()->subMonths(6),
                    'purchase_cost' => 285000.00,
                    'tax_amount' => 51300.00,
                    'total_cost' => 336300.00,
                    'salvage_value' => 16815.00,
                    'useful_life_years' => 3,
                    'depreciation_method' => 'straight_line',
                    'current_book_value' => 280250.00,
                    'accumulated_depreciation' => 56050.00,
                    'warranty_end' => Carbon::now()->addMonths(30),
                    'status' => 'Assigned',
                    'assigned_to' => $employee?->id,
                ],
                [
                    'name' => 'Dell PowerEdge R750 Rack Server',
                    'category_id' => $createdCats[0]->id,
                    'serial_number' => 'SV-DELL-882910',
                    'model_number' => 'R750-2U',
                    'brand' => 'Dell Enterprise',
                    'purchase_date' => Carbon::now()->subMonths(14),
                    'purchase_cost' => 540000.00,
                    'tax_amount' => 97200.00,
                    'total_cost' => 637200.00,
                    'salvage_value' => 31860.00,
                    'useful_life_years' => 5,
                    'depreciation_method' => 'straight_line',
                    'current_book_value' => 488500.00,
                    'accumulated_depreciation' => 148700.00,
                    'warranty_end' => Carbon::now()->addDays(22), // Expiring in 30 days!
                    'status' => 'Available',
                ],
                [
                    'name' => 'Herman Miller Aeron Ergonomic Chair (x10 set)',
                    'category_id' => $createdCats[1]->id,
                    'serial_number' => 'HM-AER-2023-SET4',
                    'model_number' => 'Aeron-B',
                    'brand' => 'Herman Miller',
                    'purchase_date' => Carbon::now()->subMonths(18),
                    'purchase_cost' => 450000.00,
                    'tax_amount' => 81000.00,
                    'total_cost' => 531000.00,
                    'salvage_value' => 53100.00,
                    'useful_life_years' => 7,
                    'depreciation_method' => 'declining_balance',
                    'current_book_value' => 418000.00,
                    'accumulated_depreciation' => 113000.00,
                    'warranty_end' => Carbon::now()->addYears(10),
                    'status' => 'Available',
                ],
                [
                    'name' => 'Industrial Automated Forklift 2.5T',
                    'category_id' => $createdCats[2]->id,
                    'serial_number' => 'FL-TOY-99824',
                    'model_number' => '8FBN25',
                    'brand' => 'Toyota Material Handling',
                    'purchase_date' => Carbon::now()->subMonths(24),
                    'purchase_cost' => 1250000.00,
                    'tax_amount' => 225000.00,
                    'total_cost' => 1475000.00,
                    'salvage_value' => 118000.00,
                    'useful_life_years' => 8,
                    'depreciation_method' => 'double_declining',
                    'current_book_value' => 1020000.00,
                    'accumulated_depreciation' => 455000.00,
                    'warranty_end' => Carbon::now()->subDays(15), // Already Expired!
                    'status' => 'Under Maintenance',
                ],
                [
                    'name' => 'Electric Delivery Cargo Van',
                    'category_id' => $createdCats[3]->id,
                    'serial_number' => 'VIN-982471629817',
                    'model_number' => 'Ace-EV-2024',
                    'brand' => 'Tata Motors',
                    'purchase_date' => Carbon::now()->subMonths(8),
                    'purchase_cost' => 980000.00,
                    'tax_amount' => 176400.00,
                    'total_cost' => 1156400.00,
                    'salvage_value' => 173460.00,
                    'useful_life_years' => 5,
                    'depreciation_method' => 'straight_line',
                    'current_book_value' => 1002000.00,
                    'accumulated_depreciation' => 154400.00,
                    'warranty_end' => Carbon::now()->addMonths(28),
                    'status' => 'Available',
                ],
                [
                    'name' => 'Cisco Catalyst 9300 48-Port Switch',
                    'category_id' => $createdCats[4]->id,
                    'serial_number' => 'FOC2438L8B4',
                    'model_number' => 'C9300-48P',
                    'brand' => 'Cisco Systems',
                    'purchase_date' => Carbon::now()->subMonths(10),
                    'purchase_cost' => 380000.00,
                    'tax_amount' => 68400.00,
                    'total_cost' => 448400.00,
                    'salvage_value' => 22420.00,
                    'useful_life_years' => 4,
                    'depreciation_method' => 'straight_line',
                    'current_book_value' => 359800.00,
                    'accumulated_depreciation' => 88600.00,
                    'warranty_end' => Carbon::now()->addDays(52), // Expiring in 60 days!
                    'status' => 'Available',
                ],
            ];

            foreach ($assetData as $idx => $item) {
                $code = sprintf('AST-2026-%06d', $idx + 1);
                $asset = Asset::create(array_merge($item, [
                    'company_id' => $company->id,
                    'department_id' => $department?->id,
                    'location_id' => $createdLocs[$idx % count($createdLocs)]->id,
                    'asset_code' => $code,
                ]));

                // If assigned, add assignment
                if ($asset->status === 'Assigned' && $employee) {
                    AssetAssignment::create([
                        'company_id' => $company->id,
                        'asset_id' => $asset->id,
                        'employee_id' => $employee->id,
                        'department_id' => $department?->id,
                        'location_id' => $asset->location_id,
                        'assigned_date' => Carbon::now()->subMonths(5),
                        'expected_return_date' => Carbon::now()->addMonths(12),
                        'condition' => 'Good',
                        'status' => 'Assigned',
                        'notes' => 'Allocated for senior engineering tasks',
                    ]);

                    AssetAssignmentHistory::create([
                        'company_id' => $company->id,
                        'asset_id' => $asset->id,
                        'previous_holder' => 'IT Warehouse',
                        'new_holder' => $employee->first_name . ' ' . $employee->last_name,
                        'assigned_date' => Carbon::now()->subMonths(5),
                        'condition' => 'Good',
                    ]);
                }

                // If under maintenance, add ticket
                if ($asset->status === 'Under Maintenance') {
                    AssetMaintenance::create([
                        'company_id' => $company->id,
                        'asset_id' => $asset->id,
                        'maintenance_type' => 'Corrective',
                        'priority' => 'High',
                        'issue' => 'Hydraulic pressure drop & sensor calibration error',
                        'vendor' => 'Toyota Heavy Machinery Services',
                        'start_date' => Carbon::today()->subDays(3),
                        'expected_completion' => Carbon::today()->addDays(4),
                        'estimated_cost' => 18500.00,
                        'status' => 'In Progress',
                    ]);
                }
            }
        }
    }

    protected function seedDocumentsModule(Company $company): void
    {
        // 1. Document Categories
        $categories = [
            ['name' => 'Contracts & Legal Agreements', 'code' => 'CAT-LEGAL', 'icon' => 'Scale'],
            ['name' => 'Finance & Invoices', 'code' => 'CAT-FIN', 'icon' => 'Receipt'],
            ['name' => 'Human Resources & Onboarding', 'code' => 'CAT-HR', 'icon' => 'Users'],
            ['name' => 'Company Policies & SOPs', 'code' => 'CAT-POL', 'icon' => 'BookOpen'],
            ['name' => 'Compliance, Tax & Licenses', 'code' => 'CAT-COMP', 'icon' => 'ShieldCheck'],
            ['name' => 'Engineering & Projects', 'code' => 'CAT-ENG', 'icon' => 'FolderGit2'],
        ];

        $createdCats = [];
        foreach ($categories as $cat) {
            $createdCats[] = DocumentCategory::firstOrCreate(
                ['company_id' => $company->id, 'code' => $cat['code']],
                array_merge($cat, ['company_id' => $company->id])
            );
        }

        // 2. Folder Hierarchy
        $rootFolders = [
            ['name' => 'HR & Workforce Records', 'slug' => 'hr', 'color' => '#0F8B7A'],
            ['name' => 'Finance & Taxation', 'slug' => 'finance', 'color' => '#2563EB'],
            ['name' => 'Legal & Corporate Governance', 'slug' => 'legal', 'color' => '#8B5CF6'],
            ['name' => 'Regulatory & ISO Compliance', 'slug' => 'compliance', 'color' => '#F59E0B'],
            ['name' => 'Operations & SOP Manuals', 'slug' => 'operations', 'color' => '#0284C7'],
        ];

        $createdFolders = [];
        foreach ($rootFolders as $rf) {
            $folder = DocumentFolder::firstOrCreate(
                ['company_id' => $company->id, 'slug' => $rf['slug']],
                array_merge($rf, ['company_id' => $company->id])
            );
            $createdFolders[] = $folder;

            // Subfolder for finance: 2026 -> Invoices, Tax
            if ($rf['slug'] === 'finance') {
                $subYear = DocumentFolder::firstOrCreate(
                    ['company_id' => $company->id, 'parent_id' => $folder->id, 'slug' => 'finance-2026'],
                    ['name' => 'FY 2025-2026', 'color' => '#3B82F6', 'company_id' => $company->id]
                );
                DocumentFolder::firstOrCreate(
                    ['company_id' => $company->id, 'parent_id' => $subYear->id, 'slug' => 'fin-tax-audit'],
                    ['name' => 'Tax Audit Reports', 'color' => '#60A5FA', 'company_id' => $company->id]
                );
            }
        }

        $user = User::where('company_id', $company->id)->first() ?: User::first();
        $department = Department::where('company_id', $company->id)->first() ?: Department::first();
        $employee = Employee::where('company_id', $company->id)->first() ?: Employee::first();

        // 3. Sample Documents
        if (Document::where('company_id', $company->id)->count() < 5) {
            $sampleDocs = [
                [
                    'name' => 'Master Services Agreement - Enterprise Client Q3',
                    'file_type' => 'pdf',
                    'confidentiality' => 'Confidential',
                    'status' => 'Approved',
                    'category_id' => $createdCats[0]->id,
                    'folder_id' => $createdFolders[2]->id,
                    'tags' => ['Contract', 'Client', 'Legal'],
                    'current_version' => 'v2.0',
                    'file_size' => 1024 * 1450,
                ],
                [
                    'name' => 'Annual Corporate Tax Filing Assessment 2025-26',
                    'file_type' => 'xlsx',
                    'confidentiality' => 'Highly Confidential',
                    'status' => 'Approved',
                    'category_id' => $createdCats[1]->id,
                    'folder_id' => $createdFolders[1]->id,
                    'tags' => ['Tax', 'Audit', 'Finance'],
                    'current_version' => 'v1.1',
                    'file_size' => 1024 * 820,
                ],
                [
                    'name' => 'Information Security & Data Protection Policy (ISO 27001)',
                    'file_type' => 'pdf',
                    'confidentiality' => 'Internal',
                    'status' => 'Approved',
                    'category_id' => $createdCats[3]->id,
                    'folder_id' => $createdFolders[4]->id,
                    'tags' => ['Security', 'ISO', 'Policy'],
                    'current_version' => 'v3.0',
                    'file_size' => 1024 * 2300,
                ],
                [
                    'name' => 'Employee Handbook & Code of Conduct 2026',
                    'file_type' => 'docx',
                    'confidentiality' => 'Public',
                    'status' => 'Approved',
                    'category_id' => $createdCats[2]->id,
                    'folder_id' => $createdFolders[0]->id,
                    'tags' => ['HR', 'Handbook', 'Conduct'],
                    'current_version' => 'v1.0',
                    'file_size' => 1024 * 1200,
                ],
                [
                    'name' => 'Municipal Trade License Renewal Certificate',
                    'file_type' => 'pdf',
                    'confidentiality' => 'Internal',
                    'status' => 'Approved',
                    'category_id' => $createdCats[4]->id,
                    'folder_id' => $createdFolders[3]->id,
                    'tags' => ['License', 'Compliance', 'Municipal'],
                    'current_version' => 'v1.0',
                    'file_size' => 1024 * 650,
                ],
            ];

            foreach ($sampleDocs as $idx => $item) {
                $docNum = sprintf('DOC-2026-%06d', $idx + 1);
                $doc = Document::create(array_merge($item, [
                    'company_id' => $company->id,
                    'document_number' => $docNum,
                    'department_id' => $department?->id,
                    'owner_id' => $user?->id,
                    'uploaded_by' => $user?->id,
                    'issue_date' => Carbon::now()->subMonths(3),
                    'expiry_date' => Carbon::now()->addMonths(9),
                ]));

                // Version record
                DocumentVersion::create([
                    'document_id' => $doc->id,
                    'version' => 'v1.0',
                    'file_path' => 'documents/samples/' . $doc->document_number . '.pdf',
                    'original_name' => $doc->name . '.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => $doc->file_size,
                    'change_summary' => 'Initial approved release',
                    'uploaded_by' => $user?->id,
                    'approved_by' => $user?->id,
                    'approved_at' => Carbon::now()->subMonths(3),
                ]);
            }
        }

        // 4. Compliance Documents with Expiry Dates
        if (DocumentCompliance::where('company_id', $company->id)->count() < 4) {
            $compliances = [
                [
                    'title' => 'GST Registration Certificate - Maharashtra Region',
                    'compliance_type' => 'GST',
                    'authority' => 'Goods & Services Tax Department, Govt of India',
                    'document_number' => 'GSTIN-27AAACG0123M1Z9',
                    'issue_date' => Carbon::now()->subYears(2),
                    'expiry_date' => Carbon::now()->addDays(14), // Expires in 14 days!
                    'status' => 'active',
                ],
                [
                    'title' => 'ISO 9001:2015 Quality Management Certification',
                    'compliance_type' => 'ISO',
                    'authority' => 'Bureau Veritas Certification',
                    'document_number' => 'ISO-QMS-9001-2024-88',
                    'issue_date' => Carbon::now()->subYears(1),
                    'expiry_date' => Carbon::now()->addDays(55), // Expires in 60 days!
                    'status' => 'active',
                ],
                [
                    'title' => 'State Fire Safety & Hazardous Material Clearance',
                    'compliance_type' => 'Licenses',
                    'authority' => 'Municipal Fire Safety Directorate',
                    'document_number' => 'FIRE-NOC-2023-994',
                    'issue_date' => Carbon::now()->subMonths(13),
                    'expiry_date' => Carbon::now()->subDays(10), // Expired!
                    'status' => 'expired',
                ],
                [
                    'title' => 'Annual Corporate Pollution Control Board Consent (CTO)',
                    'compliance_type' => 'Government Documents',
                    'authority' => 'Central Pollution Control Board',
                    'document_number' => 'PCB-CTO-88741-2025',
                    'issue_date' => Carbon::now()->subMonths(6),
                    'expiry_date' => Carbon::now()->addDays(85), // Expires in 90 days!
                    'status' => 'active',
                ],
            ];

            foreach ($compliances as $c) {
                DocumentCompliance::create(array_merge($c, [
                    'company_id' => $company->id,
                    'responsible_person_id' => $employee?->id,
                    'department_id' => $department?->id,
                ]));
            }
        }

        // 5. Policies & Manuals
        if (DocumentPolicy::where('company_id', $company->id)->count() < 4) {
            $policies = [
                ['title' => 'Global Remote Work & Flexible Hours Policy', 'policy_number' => 'POL-2026-0001', 'category' => 'HR', 'status' => 'Published', 'version' => 'v2.1', 'effective_date' => Carbon::now()->subMonths(6)],
                ['title' => 'Enterprise Cyber Security & Incident Response Manual', 'policy_number' => 'POL-2026-0002', 'category' => 'Security', 'status' => 'Published', 'version' => 'v3.0', 'effective_date' => Carbon::now()->subMonths(4)],
                ['title' => 'Corporate Travel & Business Expense Reimbursement Policy', 'policy_number' => 'POL-2026-0003', 'category' => 'Finance', 'status' => 'Review Due', 'version' => 'v1.4', 'effective_date' => Carbon::now()->subYear()],
                ['title' => 'Whistleblower Protection & Ethical Conduct Framework', 'policy_number' => 'POL-2026-0004', 'category' => 'Legal', 'status' => 'Published', 'version' => 'v1.0', 'effective_date' => Carbon::now()->subMonths(2)],
            ];

            foreach ($policies as $p) {
                DocumentPolicy::create(array_merge($p, [
                    'company_id' => $company->id,
                    'owner_id' => $user?->id,
                    'department_id' => $department?->id,
                ]));
            }
        }

        // 6. Configured Approval Workflow
        if (DocumentWorkflow::where('company_id', $company->id)->count() === 0) {
            $wf = DocumentWorkflow::create([
                'company_id' => $company->id,
                'department_id' => $department?->id,
                'name' => 'Standard Multi-Stage Enterprise Review Workflow',
                'description' => 'Mandatory review chain for high value contracts, policies, and financial statements.',
                'is_active' => true,
            ]);

            DocumentWorkflowStep::create([
                'workflow_id' => $wf->id,
                'step_name' => '1. Legal & Regulatory Reviewer',
                'sequence' => 1,
                'approver_type' => 'role',
                'approver_role' => 'Legal Counsel',
                'is_required' => true,
                'sla_hours' => 24,
            ]);

            DocumentWorkflowStep::create([
                'workflow_id' => $wf->id,
                'step_name' => '2. Departmental Executive Sign-off',
                'sequence' => 2,
                'approver_type' => 'role',
                'approver_role' => 'Manager',
                'is_required' => true,
                'sla_hours' => 48,
            ]);

            DocumentWorkflowStep::create([
                'workflow_id' => $wf->id,
                'step_name' => '3. Chief Financial Officer / Board Sign-off',
                'sequence' => 3,
                'approver_type' => 'role',
                'approver_role' => 'Super Admin',
                'is_required' => true,
                'sla_hours' => 72,
            ]);
        }
    }
}
