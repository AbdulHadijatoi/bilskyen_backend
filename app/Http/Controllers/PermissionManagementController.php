<?php

namespace App\Http\Controllers;

use App\Services\RolePermissionService;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PermissionManagementController extends Controller
{
    protected RolePermissionService $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Get all permissions grouped by entity
     * Cached for 60 seconds
     */
    public function getAllItems(Request $request)
    {
        $cacheKey = 'permissions:all_items';
        
            $items = Cache::remember($cacheKey, 60, function () {
            $permissions = $this->rolePermissionService->getAllPermissions();
            
            // Group permissions by resource
            // Permission format: {role}.{resource}.{action} (e.g., "dealer.vehicles.view", "admin.vehicles.view")
            // We only show dealer permissions (exclude admin permissions)
            // We group by resource, ignoring the role prefix
            $grouped = [];
            
            foreach ($permissions as $permission) {
                $parts = explode('.', $permission->name);
                
                // Handle format: {role}.{resource}.{action} (3 parts)
                if (count($parts) >= 3) {
                    $role = $parts[0];      // e.g., "dealer" or "admin"
                    
                    // Skip admin permissions - only show dealer permissions
                    if ($role === 'admin') {
                        continue;
                    }
                    
                    $resource = $parts[1]; // e.g., "vehicles", "users"
                    $action = $parts[2];   // e.g., "view", "create"
                    
                    // Use resource as the entity name for grouping
                    $entity = $resource;
                    
                    if (!isset($grouped[$entity])) {
                        $grouped[$entity] = [
                            'name' => $entity,
                            'actions' => []
                        ];
                    }
                    
                    // Add permission with its ID
                    $grouped[$entity]['actions'][] = [
                        'id' => $permission->id,
                        'action' => $action,
                        'status' => 0 // Will be updated based on model assignment
                    ];
                }
                // Fallback for old format: {entity}.{action} (2 parts) - for backward compatibility
                elseif (count($parts) >= 2) {
                    $entity = $parts[0];
                    $action = $parts[1];
                    
                    // Skip if it's an admin permission (starts with "admin")
                    if ($entity === 'admin') {
                        continue;
                    }
                    
                    if (!isset($grouped[$entity])) {
                        $grouped[$entity] = [
                            'name' => $entity,
                            'actions' => []
                        ];
                    }
                    
                    $grouped[$entity]['actions'][] = [
                        'id' => $permission->id,
                        'action' => $action,
                        'status' => 0
                    ];
                }
            }
            
            // Convert to indexed array and sort by entity name
            $items = array_values($grouped);
            usort($items, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            return $items;
        });

        return response()->json(['items' => $items]);
    }

    /**
     * Search for users or roles (autocomplete)
     */
    public function getModels(Request $request)
    {
        $type = $request->input('type', 'user'); // 'user' or 'role'
        $query = $request->input('query', '');
        $limit = $request->input('limit', 10);

        if ($type === 'role') {
            $models = Role::where('name', 'like', "%{$query}%")
                ->where('name', '!=', 'admin') // Exclude admin role
                ->limit($limit)
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'type' => 'role'
                    ];
                });
        } else {
            $models = User::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'type' => 'user'
                    ];
                });
        }

        return response()->json(['models' => $models]);
    }

    /**
     * Get permissions assigned to a model (user or role)
     * Returns array of permission IDs
     */
    public function modelItems(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:user,role',
            'model_id' => 'required|integer'
        ]);

        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');

        if ($modelType === 'role') {
            $model = Role::findOrFail($modelId);
            $permissions = $this->rolePermissionService->getRolePermissions($model);
        } else {
            $model = User::findOrFail($modelId);
            $permissions = $this->rolePermissionService->getUserPermissions($model);
        }

        $permissionIds = $permissions->pluck('id')->toArray();

        return response()->json(['permission_ids' => $permissionIds]);
    }

    /**
     * Assign permission to a model (user or role)
     */
    public function assign(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:user,role',
            'model_id' => 'required|integer',
            'permission_id' => 'required|integer|exists:permissions,id'
        ]);

        try {
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');
            $permissionId = $request->input('permission_id');

            $permission = Permission::findOrFail($permissionId);

            if ($modelType === 'role') {
                $model = Role::findOrFail($modelId);
                
                // Check if already assigned
                if ($model->hasPermissionTo($permission)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permission is already assigned to this role'
                    ], 400);
                }
                
                $this->rolePermissionService->assignPermissionToRole($model, $permission->name);
            } else {
                $model = User::findOrFail($modelId);
                
                // Check if already directly assigned (not from roles)
                if ($model->hasDirectPermission($permission->name)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permission is already directly assigned to this user'
                    ], 400);
                }
                
                $this->rolePermissionService->assignPermissionToUser($model, $permission->name);
            }

            // Clear all permission and role caches to ensure fresh data
            // This clears: permissions:all_items cache, Spatie Permission cache, and service caches
            Cache::forget('permissions:all_items');
            $this->rolePermissionService->clearCaches();

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to assign permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke permission from a model (user or role)
     */
    public function revoke(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:user,role',
            'model_id' => 'required|integer',
            'permission_id' => 'required|integer|exists:permissions,id'
        ]);

        try {
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');
            $permissionId = $request->input('permission_id');

            $permission = Permission::findOrFail($permissionId);

            if ($modelType === 'role') {
                $model = Role::findOrFail($modelId);
                $this->rolePermissionService->removePermissionFromRole($model, $permission->name);
            } else {
                $model = User::findOrFail($modelId);
                // Only revoke if directly assigned (not from roles)
                if ($model->hasDirectPermission($permission->name)) {
                    $this->rolePermissionService->removePermissionFromUser($model, $permission->name);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permission is not directly assigned to this user (it may be inherited from a role)'
                    ], 400);
                }
            }

            // Clear all permission and role caches to ensure fresh data
            // This clears: permissions:all_items cache, Spatie Permission cache, and service caches
            Cache::forget('permissions:all_items');
            $this->rolePermissionService->clearCaches();

            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear permissions and roles cache
     */
    public function clearCache(Request $request)
    {
        try {
            // Clear all permission-related caches
            Cache::forget('permissions:all_items');
            $this->rolePermissionService->clearCaches();

            return response()->json([
                'success' => true,
                'message' => 'Permissions and roles cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }
}

