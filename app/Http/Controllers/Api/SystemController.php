<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemAuditLog;
use App\Services\DeleteRequestService;
use App\Services\RolePermissionService;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    protected UserManagementService $userService;
    protected RolePermissionService $roleService;
    protected DeleteRequestService $deleteService;

    public function __construct(
        UserManagementService $userService,
        RolePermissionService $roleService,
        DeleteRequestService $deleteService
    ) {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->deleteService = $deleteService;
    }

    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    // --- Users ---
    public function users(Request $request): JsonResponse
    {
        $users = $this->userService->getUsers($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $users]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'phone' => 'nullable|string',
            'department_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $user = $this->userService->createUser($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $user, 'message' => 'User created successfully.']);
    }

    public function showUser(Request $request, int $id): JsonResponse
    {
        $data = $this->userService->getUserDetails($id, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->updateUser($id, $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $user, 'message' => 'User updated successfully.']);
    }

    public function toggleUserStatus(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->toggleStatus($id, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $user, 'message' => 'User status toggled.']);
    }

    public function resetUserPassword(Request $request, int $id): JsonResponse
    {
        $request->validate(['new_password' => 'required|string|min:6']);
        $this->userService->resetPassword($id, $request->input('new_password'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Password reset successfully.']);
    }

    // --- Roles & Permissions ---
    public function roles(Request $request): JsonResponse
    {
        $roles = $this->roleService->getRoles();
        return response()->json(['status' => 'success', 'data' => $roles]);
    }

    public function storeRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'nullable|string',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
        ]);

        $role = $this->roleService->createRole($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $role, 'message' => 'Role created successfully.']);
    }

    public function permissions(Request $request): JsonResponse
    {
        $permissions = $this->roleService->getPermissionsGrouped();
        return response()->json(['status' => 'success', 'data' => $permissions]);
    }

    public function syncRolePermissions(Request $request, int $id): JsonResponse
    {
        $request->validate(['permission_ids' => 'required|array']);
        $role = $this->roleService->syncRolePermissions($id, $request->input('permission_ids'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $role, 'message' => 'Permissions synchronized successfully.']);
    }

    // --- Delete Requests ---
    public function deleteRequests(Request $request): JsonResponse
    {
        $requests = $this->deleteService->getRequests($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $requests]);
    }

    public function storeDeleteRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => 'required|string',
            'record_id' => 'required|integer',
            'record_title' => 'required|string',
            'reason' => 'required|string',
        ]);

        $item = $this->deleteService->submitRequest($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $item, 'message' => 'Deletion request submitted for approval.']);
    }

    public function approveDeleteRequest(Request $request, int $id): JsonResponse
    {
        $item = $this->deleteService->approveRequest($id, $request->input('review_notes'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $item, 'message' => 'Delete request approved and record soft deleted.']);
    }

    public function rejectDeleteRequest(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);
        $item = $this->deleteService->rejectRequest($id, $request->input('reason'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $item, 'message' => 'Delete request rejected.']);
    }

    // --- Audit Logs ---
    public function auditLogs(Request $request): JsonResponse
    {
        $query = SystemAuditLog::where('company_id', $this->getCompanyId($request))->with('user');

        if ($request->has('module') && $request->input('module') !== 'all') {
            $query->where('module', $request->input('module'));
        }

        $perPage = min(100, max(5, (int) ($request->input('per_page', 20))));
        $logs = $query->latest('created_at')->paginate($perPage);

        return response()->json(['status' => 'success', 'data' => $logs]);
    }
}
