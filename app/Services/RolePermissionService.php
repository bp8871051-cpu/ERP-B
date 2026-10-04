<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    /**
     * Get all roles with permission count and users assigned count
     */
    public function getRoles(): array
    {
        $roles = Role::with('permissions')->get();

        return $roles->map(function ($r) {
            $userCount = User::where('role', $r->name)->count();
            return [
                'id' => $r->id,
                'name' => $r->name,
                'display_name' => $r->display_name ?: $r->name,
                'description' => $r->description,
                'users_count' => $userCount,
                'permissions_count' => $r->permissions->count(),
                'permissions' => $r->permissions->pluck('name')->toArray(),
                'permission_ids' => $r->permissions->pluck('id')->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissionsGrouped(): array
    {
        // Ensure standard enterprise permissions exist
        $this->ensureEnterprisePermissions();

        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $p) {
            $module = $p->module ?: 'General';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
            ];
        }

        return [
            'grouped' => $grouped,
            'list' => $permissions,
        ];
    }

    /**
     * Update permissions for a specific role
     */
    public function syncRolePermissions(int $roleId, array $permissionIds, int $companyId = 1, ?int $userId = null): Role
    {
        $role = Role::findOrFail($roleId);
        $role->permissions()->sync($permissionIds);

        SystemAuditLog::log('system', 'update_role_permissions', (string) $role->id, null, ['permission_count' => count($permissionIds)], $companyId, $userId);

        return $role->load('permissions');
    }

    /**
     * Create new custom role
     */
    public function createRole(array $data, int $companyId = 1, ?int $userId = null): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'] ?? $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if (!empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        SystemAuditLog::log('system', 'create_role', (string) $role->id, null, $role->toArray(), $companyId, $userId);

        return $role->load('permissions');
    }

    /**
     * Ensure baseline enterprise permissions exist across all 15 modules
     */
    protected function ensureEnterprisePermissions(): void
    {
        $modules = [
            'Dashboard' => ['view', 'export'],
            'CRM' => ['view', 'create', 'edit', 'delete', 'export'],
            'Sales' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'Purchase' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'Inventory' => ['view', 'create', 'edit', 'delete', 'manage_stock', 'export'],
            'POS' => ['access_terminal', 'process_orders', 'issue_refunds', 'print_barcode', 'view_reports'],
            'Finance' => ['view', 'create', 'edit', 'delete', 'approve_payments', 'export'],
            'HRM' => ['view_employees', 'manage_payroll', 'approve_leaves', 'manage_attendance'],
            'Assets' => ['view', 'create', 'edit', 'delete', 'depreciate', 'maintenance', 'dispose'],
            'Documents' => ['view', 'upload', 'version', 'approve_workflow', 'manage_compliance'],
            'Support' => ['view_tickets', 'create_tickets', 'reply_tickets', 'assign_tickets', 'manage_sla', 'manage_kb'],
            'Membership' => ['view_plans', 'manage_members', 'process_renewals', 'view_transactions'],
            'Reports' => ['view', 'export', 'schedule'],
            'System' => ['manage_users', 'manage_roles', 'approve_delete_requests', 'view_audit_logs'],
            'Settings' => ['manage_general', 'manage_security', 'manage_notifications', 'manage_integrations'],
        ];

        foreach ($modules as $modName => $actions) {
            foreach ($actions as $action) {
                $permName = strtolower(str_replace(' ', '_', $modName)) . '.' . $action;
                Permission::firstOrCreate(
                    ['name' => $permName],
                    [
                        'module' => $modName,
                        'description' => ucfirst(str_replace('_', ' ', $action)) . " permission for {$modName}",
                    ]
                );
            }
        }
    }
}
