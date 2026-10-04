<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSyncSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::all();

        // 1. Super Admin & Admin get ALL permissions
        $allPermIds = $allPermissions->pluck('id')->toArray();
        Role::where('name', 'Super Admin')->first()?->permissions()->sync($allPermIds);
        Role::where('name', 'Admin')->first()?->permissions()->sync($allPermIds);

        // 2. HR Manager
        $hrPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'hrm.') || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'HR Manager')->first()?->permissions()->sync($hrPermIds);

        // 3. Inventory Manager
        $invPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'inventory.') || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'Inventory Manager')->first()?->permissions()->sync($invPermIds);

        // 4. CRM Manager
        $crmPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'crm.') || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'CRM Manager')->first()?->permissions()->sync($crmPermIds);

        // 5. Sales Manager
        $salesPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'sales.') || str_starts_with($p->name, 'crm.') || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'Sales Manager')->first()?->permissions()->sync($salesPermIds);

        // 6. Finance Manager
        $finPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'finance.') || str_starts_with($p->name, 'reports.') || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'Finance Manager')->first()?->permissions()->sync($finPermIds);

        // 7. Procurement Manager
        $procPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'procurement.') || str_starts_with($p->name, 'purchase.') || $p->name === 'inventory.view' || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'Procurement Manager')->first()?->permissions()->sync($procPermIds);

        // 8. Project Manager
        $projPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'projects.') || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'Project Manager')->first()?->permissions()->sync($projPermIds);

        // 9. Support Manager
        $supPermIds = $allPermissions->filter(function ($p) {
            return str_starts_with($p->name, 'support.') || $p->name === 'dashboard.view';
        })->pluck('id')->toArray();
        Role::where('name', 'Support Manager')->first()?->permissions()->sync($supPermIds);

        // 10. Employee
        $empPermIds = $allPermissions->filter(function ($p) {
            return in_array($p->name, ['dashboard.view', 'hrm.view', 'hrm.manage_attendance']);
        })->pluck('id')->toArray();
        Role::where('name', 'Employee')->first()?->permissions()->sync($empPermIds);
    }
}
