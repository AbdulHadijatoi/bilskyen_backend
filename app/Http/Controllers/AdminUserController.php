<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Constants\UserStatus;
use App\Services\RolePermissionService;
use App\Services\AuditLogService;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService,
        private AuditLogService $auditLogService
    ) {}

    /**
     * Get users list
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles', 'userStatus');

        // Apply filters
        $filters = json_decode($request->input('filters', '[]'), true);
        $joinOperator = $request->input('joinOperator', 'or');
        FilterHelper::applyFilters($query, $filters, $joinOperator);

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        FilterHelper::applySorting($query, $sort);

        // Paginate
        $perPage = $request->input('limit', 15);
        $users = $query->paginate($perPage);

        return $this->paginated($users);
    }

    /**
     * Get user details
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles', 'userStatus', 'dealers')->findOrFail($id);
        return $this->success($user);
    }

    /**
     * Create user
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:128',
            'phone' => 'nullable|string|max:15',
            'status_id' => ['required', Rule::in(UserStatus::values())],
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'phone' => $request->phone,
            'status_id' => $request->status_id,
        ]);

        // Assign roles
        if ($request->has('roles')) {
            $this->rolePermissionService->assignRoleToUser($user, $request->roles);
        }

        // Audit log
        $this->auditLogService->log(
            $request->user()->id,
            \App\Models\AuditActorType::ADMIN,
            'create',
            'User',
            $user->id,
            null,
            $user->toArray(),
            $request
        );

        return $this->created($user->load('roles', 'userStatus'));
    }

    /**
     * Update user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $before = $user->toArray();

        $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:15',
            'status_id' => ['sometimes', Rule::in(UserStatus::values())],
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->update($request->only(['name', 'email', 'phone', 'status_id']));

        // Update roles if provided
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        // Audit log
        $this->auditLogService->log(
            $request->user()->id,
            \App\Models\AuditActorType::ADMIN,
            'update',
            'User',
            $user->id,
            $before,
            $user->fresh()->toArray(),
            $request
        );

        return $this->success($user->load('roles', 'userStatus'));
    }

    /**
     * Delete user (soft delete)
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);
        $before = $user->toArray();

        $user->delete();

        // Audit log
        $this->auditLogService->logDelete(
            $request->user()->id,
            \App\Models\AuditActorType::ADMIN,
            'User',
            $user->id,
            $before,
            $request
        );

        return $this->noContent();
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status_id' => ['required', Rule::in(UserStatus::values())],
        ]);

        $user = User::findOrFail($id);
        $before = ['status_id' => $user->status_id];
        
        $user->status_id = $request->status_id;
        $user->save();

        $after = ['status_id' => $user->status_id];

        // Audit log
        $this->auditLogService->log(
            $request->user()->id,
            \App\Models\AuditActorType::ADMIN,
            'status_change',
            'User',
            $user->id,
            $before,
            $after,
            $request
        );

        return $this->success($user->load('userStatus'));
    }

    /**
     * Ban user
     */
    public function ban(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $before = $user->toArray();

        // Update status to suspended
        $user->status_id = UserStatus::SUSPENDED;
        $user->save();

        // Audit log
        $this->auditLogService->logUserBan(
            $request->user()->id,
            $user->id,
            $before,
            $request
        );

        return $this->success($user->load('userStatus'));
    }

    /**
     * Unban user
     */
    public function unban(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $before = $user->toArray();

        // Update status to active
        $user->status_id = UserStatus::ACTIVE;
        $user->save();

        // Audit log
        $this->auditLogService->logUserUnban(
            $request->user()->id,
            $user->id,
            $before,
            $request
        );

        return $this->success($user->load('userStatus'));
    }

    /**
     * Get users list (legacy method for backward compatibility)
     */
    public function getUsers(Request $request): JsonResponse
    {
        return $this->index($request);
    }
}
