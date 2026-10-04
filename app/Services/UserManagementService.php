<?php

namespace App\Services;

use App\Models\Department;
use App\Models\LoginHistory;
use App\Models\Role;
use App\Models\SystemAuditLog;
use App\Models\User;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementService
{
    /**
     * Get paginated users
     */
    public function getUsers(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = User::where('company_id', $companyId)
            ->with(['department', 'company']);

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('role', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['department_id']) && $filters['department_id'] !== 'all') {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== 'all') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 15)));
        return $query->latest()->paginate($perPage);
    }

    /**
     * Create new user
     */
    public function createUser(array $data, int $companyId = 1, ?int $actorId = null): User
    {
        $user = User::create([
            'company_id' => $companyId,
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password'] ?? 'Password123!'),
            'role' => $data['role'] ?? 'Employee',
            'is_active' => $data['is_active'] ?? true,
        ]);

        SystemAuditLog::log('system', 'create_user', (string) $user->id, null, $user->only(['name', 'email', 'role']), $companyId, $actorId);

        return $user->load(['department', 'company']);
    }

    /**
     * Update user
     */
    public function updateUser(int $id, array $data, int $companyId = 1, ?int $actorId = null): User
    {
        $user = User::where('company_id', $companyId)->findOrFail($id);
        $old = $user->only(['name', 'email', 'role', 'department_id', 'is_active']);

        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'role' => $data['role'] ?? $user->role,
            'department_id' => $data['department_id'] ?? $user->department_id,
        ];

        if (isset($data['is_active'])) {
            $updateData['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        SystemAuditLog::log('system', 'update_user', (string) $user->id, $old, $user->only(['name', 'email', 'role', 'department_id', 'is_active']), $companyId, $actorId);

        return $user->load(['department', 'company']);
    }

    /**
     * Toggle User Active Status
     */
    public function toggleStatus(int $id, int $companyId = 1, ?int $actorId = null): User
    {
        $user = User::where('company_id', $companyId)->findOrFail($id);
        $old = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        SystemAuditLog::log('system', 'toggle_user_status', (string) $user->id, ['is_active' => $old], ['is_active' => $user->is_active], $companyId, $actorId);

        return $user;
    }

    /**
     * Reset Password
     */
    public function resetPassword(int $id, string $newPassword, int $companyId = 1, ?int $actorId = null): bool
    {
        $user = User::where('company_id', $companyId)->findOrFail($id);
        $user->password = Hash::make($newPassword);
        $user->save();

        SystemAuditLog::log('system', 'reset_password', (string) $user->id, null, ['status' => 'password_reset_by_admin'], $companyId, $actorId);

        return true;
    }

    /**
     * Get user details with recent audit logs and login history
     */
    public function getUserDetails(int $id, int $companyId = 1): array
    {
        $user = User::where('company_id', $companyId)
            ->with(['department', 'company'])
            ->findOrFail($id);

        $loginHistory = LoginHistory::where('user_id', $user->id)
            ->latest('login_at')
            ->take(10)
            ->get();

        $auditLogs = SystemAuditLog::where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->take(15)
            ->get();

        return [
            'user' => $user,
            'login_history' => $loginHistory,
            'audit_logs' => $auditLogs,
        ];
    }
}
