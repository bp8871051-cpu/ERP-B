<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CanonicalRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * The 14 official system roles requested by the user:
     * 1. Field Sales
     * 2. HR Manager
     * 3. Purchase Manager
     * 4. Service Engineer
     * 5. Store Executive
     * 6. Licensing Manager
     * 7. Tally Sales
     * 8. Sales Manager
     * 9. Quantity Surveyor
     * 10. Counter Engineer
     * 11. Super Admin
     * 12. Project Engineer
     * 13. Project Manager
     * 14. Document Manager / Checker
     */
    public function run(): void
    {
        $canonicalRoles = [
            'Field Sales' => [
                'display_name' => 'Field Sales',
                'description' => 'Field sales representative handling leads, visits, quotations and customer acquisition.',
                'modules' => ['crm', 'sales', 'dashboard'],
                'demo_email' => 'fieldsales@falconerp.com',
                'demo_name' => 'Kunal Joshi',
            ],
            'HR Manager' => [
                'display_name' => 'HR Manager',
                'description' => 'Human Resources head managing payroll, attendance, leave approvals, recruitment and staff onboarding.',
                'modules' => ['hrm', 'dashboard'],
                'demo_email' => 'hr@falconerp.com',
                'demo_name' => 'Sarah Jenkins',
            ],
            'Purchase Manager' => [
                'display_name' => 'Purchase Manager',
                'description' => 'Procurement & Purchase leader managing purchase orders, vendor contracts and material procurement.',
                'modules' => ['purchase', 'procurement', 'inventory', 'dashboard'],
                'demo_email' => 'purchase@falconerp.com',
                'demo_name' => 'Rahul Sharma',
            ],
            'Service Engineer' => [
                'display_name' => 'Service Engineer',
                'description' => 'Field operations technician managing breakdown tickets, scheduled site visits, service reports and spare parts.',
                'modules' => ['service', 'support', 'dashboard'],
                'demo_email' => 'service@falconerp.com',
                'demo_name' => 'Rajesh Sharma',
            ],
            'Store Executive' => [
                'display_name' => 'Store Executive',
                'description' => 'Warehouse & store keeper managing inward/outward registers, material issuances, adjustments and warehouse audits.',
                'modules' => ['store', 'inventory', 'purchase', 'dashboard'],
                'demo_email' => 'store@falconerp.com',
                'demo_name' => 'Vikram Patel',
            ],
            'Licensing Manager' => [
                'display_name' => 'Licensing Manager',
                'description' => 'Enterprise compliance & software licensing manager tracking subscriptions, memberships and licenses.',
                'modules' => ['membership', 'documents', 'settings', 'dashboard'],
                'demo_email' => 'licensing@falconerp.com',
                'demo_name' => 'Pooja Mehta',
            ],
            'Tally Sales' => [
                'display_name' => 'Tally Sales',
                'description' => 'Billing and sales desk specialist handling invoices, POS registers, receipts and Tally accounting reconciliations.',
                'modules' => ['pos', 'sales', 'finance', 'crm', 'dashboard'],
                'demo_email' => 'tallysales@falconerp.com',
                'demo_name' => 'Nirav Shah',
            ],
            'Sales Manager' => [
                'display_name' => 'Sales Manager',
                'description' => 'Head of Sales & CRM overseeing sales pipeline, deals, customer relations, quotes and sales staff.',
                'modules' => ['sales', 'crm', 'pos', 'reports', 'dashboard'],
                'demo_email' => 'sales@falconerp.com',
                'demo_name' => 'Elena Rostova',
            ],
            'Quantity Surveyor' => [
                'display_name' => 'Quantity Surveyor',
                'description' => 'Cost estimator and site quantity surveyor monitoring project quantities, BOM costs, site expenses and claims.',
                'modules' => ['projects', 'finance', 'inventory', 'store', 'dashboard'],
                'demo_email' => 'qs@falconerp.com',
                'demo_name' => 'Karan Trivedi',
            ],
            'Counter Engineer' => [
                'display_name' => 'Counter Engineer',
                'description' => 'In-house technical desk engineer handling direct customer service walk-ins, diagnostic repairs and parts issue.',
                'modules' => ['service', 'support', 'inventory', 'pos', 'dashboard'],
                'demo_email' => 'counter@falconerp.com',
                'demo_name' => 'Deepak Verma',
            ],
            'Super Admin' => [
                'display_name' => 'Super Admin',
                'description' => 'Unrestricted system owner with full enterprise administrative access, security settings and controls.',
                'modules' => ['*'],
                'demo_email' => 'admin@falconerp.com',
                'demo_name' => 'Alexander Wright',
            ],
            'Project Engineer' => [
                'display_name' => 'Project Engineer',
                'description' => 'Site execution engineer tracking project milestones, daily progress logs, timesheets and technical evaluations.',
                'modules' => ['projects', 'documents', 'service', 'store', 'dashboard'],
                'demo_email' => 'projecteng@falconerp.com',
                'demo_name' => 'Amit Verma',
            ],
            'Project Manager' => [
                'display_name' => 'Project Manager',
                'description' => 'Senior project delivery manager supervising multiple project sites, engineers, budgets and timelines.',
                'modules' => ['projects', 'documents', 'finance', 'reports', 'dashboard'],
                'demo_email' => 'projectmgr@falconerp.com',
                'demo_name' => 'Rachel Zhang',
            ],
            'Document Manager / Checker' => [
                'display_name' => 'Document Manager / Checker',
                'description' => 'Quality assurance and compliance document checker verifying drawings, submittals, versions and contracts.',
                'modules' => ['documents', 'qc', 'projects', 'dashboard'],
                'demo_email' => 'docchecker@falconerp.com',
                'demo_name' => 'Sanjay Parekh',
            ],
        ];

        $allPermissions = Permission::all();
        $allPermIds = $allPermissions->pluck('id')->toArray();

        // 1. Create / Update the 14 Canonical Roles
        $canonicalRoleNames = array_keys($canonicalRoles);
        $roleModels = [];

        foreach ($canonicalRoles as $rName => $cfg) {
            $role = Role::firstOrCreate(
                ['name' => $rName],
                [
                    'display_name' => $cfg['display_name'],
                    'description' => $cfg['description'],
                ]
            );

            // Update description and display_name if needed
            $role->update([
                'display_name' => $cfg['display_name'],
                'description' => $cfg['description'],
            ]);

            // Sync appropriate permissions
            if (in_array('*', $cfg['modules'])) {
                $role->permissions()->sync($allPermIds);
            } else {
                $permIds = $allPermissions->filter(function ($p) use ($cfg) {
                    if ($p->name === 'dashboard.view') return true;
                    foreach ($cfg['modules'] as $mod) {
                        if (str_starts_with(strtolower($p->name), strtolower($mod) . '.') ||
                            strtolower($p->module) === strtolower($mod)) {
                            return true;
                        }
                    }
                    return false;
                })->pluck('id')->toArray();

                $role->permissions()->sync($permIds);
            }

            $roleModels[$rName] = $role;

            // Ensure a demo user exists for this role for smooth testing and preview
            $user = User::where('email', $cfg['demo_email'])->first();
            if (!$user) {
                User::create([
                    'company_id' => 1,
                    'name' => $cfg['demo_name'],
                    'email' => $cfg['demo_email'],
                    'password' => Hash::make('password'),
                    'role' => $rName,
                    'phone' => '+91 98250 ' . rand(10000, 99999),
                    'is_active' => true,
                ]);
            } else {
                $user->update([
                    'role' => $rName,
                    'name' => $cfg['demo_name'],
                    'is_active' => true,
                ]);
            }
        }

        // 2. Re-map any users currently assigned to obsolete roles to the closest canonical role
        $roleMapping = [
            'Admin' => 'Super Admin',
            'Inventory Manager' => 'Store Executive',
            'CRM Manager' => 'Sales Manager',
            'Finance Manager' => 'Tally Sales',
            'Procurement Manager' => 'Purchase Manager',
            'Support Manager' => 'Service Engineer',
            'Support Agent' => 'Counter Engineer',
            'Sales Representative' => 'Field Sales',
            'Viewer' => 'Document Manager / Checker',
            'Quality Control' => 'Document Manager / Checker',
            'Manager' => 'Project Manager',
            'Employee' => 'Project Engineer',
        ];

        foreach ($roleMapping as $oldRole => $targetRole) {
            User::where('role', $oldRole)->update(['role' => $targetRole]);
        }

        // Safety fallback for any user with unlisted role
        User::whereNotIn('role', $canonicalRoleNames)->update(['role' => 'Project Engineer']);

        // 3. Delete obsolete roles from the roles table (and their role_permissions pivot)
        $obsoleteRoles = Role::whereNotIn('name', $canonicalRoleNames)->get();
        foreach ($obsoleteRoles as $obsRole) {
            $obsRole->permissions()->detach();
            $obsRole->delete();
        }
    }
}
