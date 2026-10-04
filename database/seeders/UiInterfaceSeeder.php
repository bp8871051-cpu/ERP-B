<?php

namespace Database\Seeders;

use App\Models\UiDemoRecord;
use App\Models\UiDragDropItem;
use Illuminate\Database\Seeder;

class UiInterfaceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed UI Demo Records
        $records = [
            ['name' => 'Sophia Montgomery', 'email' => 'sophia.m@falconerp.com', 'role' => 'Lead Architect', 'department' => 'Engineering', 'status' => 'active', 'salary' => 125000, 'joined_date' => '2023-01-15'],
            ['name' => 'Marcus Vance', 'email' => 'marcus.v@falconerp.com', 'role' => 'Senior DevOps Engineer', 'department' => 'Cloud Ops', 'status' => 'active', 'salary' => 110000, 'joined_date' => '2023-03-20'],
            ['name' => 'Elena Rostova', 'email' => 'elena.r@falconerp.com', 'role' => 'Principal Product Designer', 'department' => 'UI/UX Design', 'status' => 'active', 'salary' => 98000, 'joined_date' => '2023-05-12'],
            ['name' => 'Liam Chen', 'email' => 'liam.c@falconerp.com', 'role' => 'Fullstack Developer', 'department' => 'Engineering', 'status' => 'pending', 'salary' => 85000, 'joined_date' => '2024-02-01'],
            ['name' => 'Amara Okafor', 'email' => 'amara.o@falconerp.com', 'role' => 'Financial Controller', 'department' => 'Finance', 'status' => 'active', 'salary' => 115000, 'joined_date' => '2022-11-10'],
            ['name' => 'David Kim', 'email' => 'david.k@falconerp.com', 'role' => 'Supply Chain Manager', 'department' => 'Inventory', 'status' => 'active', 'salary' => 92000, 'joined_date' => '2023-08-18'],
            ['name' => 'Chloe Bennett', 'email' => 'chloe.b@falconerp.com', 'role' => 'HR Business Partner', 'department' => 'Human Resources', 'status' => 'inactive', 'salary' => 78000, 'joined_date' => '2023-09-05'],
            ['name' => 'Gabriel Torres', 'email' => 'gabriel.t@falconerp.com', 'role' => 'Customer Success Lead', 'department' => 'Support', 'status' => 'active', 'salary' => 74000, 'joined_date' => '2024-01-10'],
            ['name' => 'Isabella Santos', 'email' => 'isabella.s@falconerp.com', 'role' => 'CRM Operations Lead', 'department' => 'Sales', 'status' => 'active', 'salary' => 88000, 'joined_date' => '2023-06-25'],
            ['name' => 'Oliver Wright', 'email' => 'oliver.w@falconerp.com', 'role' => 'Security Compliance Officer', 'department' => 'Legal & InfoSec', 'status' => 'active', 'salary' => 105000, 'joined_date' => '2022-07-14'],
            ['name' => 'Zara Al-Mansoor', 'email' => 'zara.m@falconerp.com', 'role' => 'Data Analyst', 'department' => 'Analytics', 'status' => 'pending', 'salary' => 82000, 'joined_date' => '2024-03-15'],
            ['name' => 'Ethan Walker', 'email' => 'ethan.w@falconerp.com', 'role' => 'Quality Assurance Lead', 'department' => 'Engineering', 'status' => 'active', 'salary' => 89000, 'joined_date' => '2023-04-10'],
            ['name' => 'Maya Patel', 'email' => 'maya.p@falconerp.com', 'role' => 'Procurement Specialist', 'department' => 'Inventory', 'status' => 'suspended', 'salary' => 72000, 'joined_date' => '2023-10-02'],
            ['name' => 'Lucas Bernard', 'email' => 'lucas.b@falconerp.com', 'role' => 'ERP Consultant', 'department' => 'Professional Services', 'status' => 'active', 'salary' => 99000, 'joined_date' => '2023-02-28'],
            ['name' => 'Aria Tanaka', 'email' => 'aria.t@falconerp.com', 'role' => 'Frontend Specialist', 'department' => 'UI/UX Design', 'status' => 'active', 'salary' => 91000, 'joined_date' => '2023-11-20'],
        ];

        foreach ($records as $rec) {
            UiDemoRecord::updateOrCreate(['email' => $rec['email']], $rec);
        }

        // 2. Seed UI Drag & Drop Kanban Items
        $dragItems = [
            ['title' => 'Design System Tokens', 'description' => 'Standardize Tailwind color tokens for high-contrast accessibility.', 'status' => 'todo', 'priority' => 'high', 'order_index' => 1, 'assigned_to' => 'Elena Rostova'],
            ['title' => 'Sanctum 2FA Refresh', 'description' => 'Implement multi-factor authentication tokens in user profile.', 'status' => 'todo', 'priority' => 'urgent', 'order_index' => 2, 'assigned_to' => 'Marcus Vance'],
            ['title' => 'Recharts Performance Audit', 'description' => 'Profile SVG re-renders on multi-panel analytics dashboards.', 'status' => 'todo', 'priority' => 'medium', 'order_index' => 3, 'assigned_to' => 'Liam Chen'],

            ['title' => 'Data Table CSV Export', 'description' => 'Stream high-volume CSV generation using PHP response streams.', 'status' => 'in_progress', 'priority' => 'high', 'order_index' => 1, 'assigned_to' => 'Sophia Montgomery'],
            ['title' => 'Filament v5 Resource Upgrades', 'description' => 'Ensure all Schemas and Tables comply with static property typings.', 'status' => 'in_progress', 'priority' => 'medium', 'order_index' => 2, 'assigned_to' => 'Oliver Wright'],

            ['title' => 'Form Editor Rich Text Support', 'description' => 'Integrate heading, bold, italic, and lists into document editor.', 'status' => 'review', 'priority' => 'high', 'order_index' => 1, 'assigned_to' => 'Aria Tanaka'],
            ['title' => 'Barcode Print Template Alignment', 'description' => 'Calibrate thermal printer pixel density for POS register screens.', 'status' => 'review', 'priority' => 'low', 'order_index' => 2, 'assigned_to' => 'David Kim'],

            ['title' => 'MySQL MariaDB Engine Configuration', 'description' => 'Successfully restored InnoDB buffer pool and table migration schemas.', 'status' => 'done', 'priority' => 'urgent', 'order_index' => 1, 'assigned_to' => 'Alexander Wright'],
            ['title' => 'Enterprise Navigation Breadcrumb', 'description' => 'Built dynamic route segment breadcrumbs with responsive truncations.', 'status' => 'done', 'priority' => 'medium', 'order_index' => 2, 'assigned_to' => 'Elena Rostova'],
        ];

        foreach ($dragItems as $item) {
            UiDragDropItem::updateOrCreate(['title' => $item['title']], $item);
        }
    }
}
