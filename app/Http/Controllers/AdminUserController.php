<?php

namespace App\Http\Controllers;

use App\Constants\UserStatus;
use App\Helpers\FilterHelper;
use App\Models\Dealer;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\FileService;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService,
        private AuditLogService $auditLogService,
        private FileService $fileService
    ) {}

    /**
     * Get users list
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles', 'userStatus');

        // Apply direct query parameter filters
        if ($request->has('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        // Apply search filter
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($request->has('role') && $request->input('role')) {
            $roleName = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        // Apply advanced JSON filters (for backward compatibility)
        $filters = json_decode($request->input('filters', '[]'), true);
        if (! empty($filters)) {
            $joinOperator = $request->input('joinOperator', 'or');
            FilterHelper::applyFilters($query, $filters, $joinOperator);
        }

        // Apply sorting
        $sort = json_decode($request->input('sort', '[]'), true);
        if (empty($sort)) {
            // Default sorting by created_at desc
            $query->orderBy('created_at', 'desc');
        } else {
            FilterHelper::applySorting($query, $sort);
        }

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
        $user = User::with('roles', 'userStatus', 'dealer')->findOrFail($id);

        return $this->success($user);
    }

    /**
     * Create user
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:128',
            'phone' => 'nullable|string|max:15',
            'status_id' => ['required', Rule::in(UserStatus::values())],
            'role_id' => 'required|integer|exists:roles,id',
            'logo' => 'nullable|image|max:2048',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'phone' => $request->phone,
            'status_id' => $request->status_id,
        ]);

        // Assign role by ID
        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        // If dealer role, create dealer record and link to user
        if (strtolower($role->name) === 'dealer') {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
                $logoPath = $file->storeAs('dealer-logos', $filename, 'public');
                $logoUrl = Storage::disk('public')->url($logoPath);
                $this->fileService->optimizeImageForWeb($logoUrl, $file->getSize());
            }

            DB::transaction(function () use ($user, $logoPath) {
                // Make a unique slug
                $baseSlug = Str::slug($user->name.'-'.$user->id);
                $slug = $baseSlug;
                $count = 1;
                while (Dealer::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$count;
                    $count++;
                }

                Dealer::create([
                    'user_id' => $user->id,
                    'slug' => $slug,
                    'cvr' => 'PENDING-'.$user->id,
                    'address' => '',
                    'city' => '',
                    'postcode' => '',
                    'country_code' => 'DK',
                    'logo_path' => $logoPath,
                ]);
            });
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

        return $this->created($user->load('roles', 'userStatus', 'dealer'));
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
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$id,
            'phone' => 'nullable|string|max:15',
            'status_id' => ['sometimes', Rule::in(UserStatus::values())],
            'role_id' => 'sometimes|integer|exists:roles,id',
        ]);

        $user->update($request->only(['name', 'email', 'phone', 'status_id']));

        // Update role if provided
        if ($request->has('role_id')) {
            $role = Role::findOrFail($request->role_id);
            $user->syncRoles([$role]);
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
    public function delete(int $id, Request $request): JsonResponse
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

    /**
     * Get all roles (cached forever since roles are fixed)
     */
    public function getRoles(): JsonResponse
    {
        $roles = Cache::rememberForever('admin_roles_all', function () {
            return Role::orderBy('name')->get();
        });

        return $this->success($roles);
    }

    /**
     * Change user password (admin can change other users' passwords without current password)
     */
    public function changePassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $before = ['password' => '***'];

        $request->validate([
            'password' => 'required|string|min:8|max:128',
        ]);

        $user->password = $request->password;
        $user->save();

        // Audit log
        $this->auditLogService->log(
            $request->user()->id,
            \App\Models\AuditActorType::ADMIN,
            'password_change',
            'User',
            $user->id,
            $before,
            ['password' => '***'],
            $request
        );

        return $this->success(['message' => __('messages.errors.password_changed_success')]);
    }

    /**
     * Change admin's own password
     * Requires current password verification and admin role check
     */
    public function changeOwnPassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verify user is an admin
        if (! $user->hasRole('admin')) {
            return $this->error(__('messages.api.only_admins_endpoint'), null, 403);
        }

        // Match frontend API format: current_password, password, password_confirmation
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => 'required|string|max:128',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
            ],
            'password_confirmation' => 'required|string|same:password',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Verify current password
        if (! \Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return $this->error(__('messages.errors.current_password_incorrect'), [
                'current_password' => [__('messages.errors.current_password_incorrect')],
            ], 401);
        }

        $before = ['password' => '***'];

        // Update password
        $user->password = $request->password;
        $user->save();

        // Audit log
        $this->auditLogService->log(
            $user->id,
            \App\Models\AuditActorType::ADMIN,
            'password_change',
            'User',
            $user->id,
            $before,
            ['password' => '***'],
            $request
        );

        return $this->success(['message' => __('messages.errors.password_changed_success')]);
    }
}
