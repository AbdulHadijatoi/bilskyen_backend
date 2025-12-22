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
            
            // Group permissions by entity (e.g., "vehicle.list" -> entity: "vehicle", action: "list")
            $grouped = [];
            
            foreach ($permissions as $permission) {
                $parts = explode('.', $permission->name);
                if (count($parts) >= 2) {
                    $entity = $parts[0];
                    $action = $parts[1];
                    
                    if (!isset($grouped[$entity])) {
                        $grouped[$entity] = [
                            'name' => $entity,
                            'actions' => []
                        ];
                    }
                    
                    $grouped[$entity]['actions'][] = [
                        'id' => $permission->id,
                        'action' => $action,
                        'status' => 0 // Will be updated based on model assignment
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
                
                // Check if already assigned
                if ($model->hasPermissionTo($permission)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permission is already assigned to this user'
                    ], 400);
                }
                
                $this->rolePermissionService->assignPermissionToUser($model, $permission->name);
            }

            // Clear cache
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
                $this->rolePermissionService->removePermissionFromUser($model, $permission->name);
            }

            // Clear cache
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
}

