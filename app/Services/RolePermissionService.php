<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionService
{
    /**
     * Cache keys
     */
    private const CACHE_KEY_ALL_PERMISSIONS = 'roles_permissions:all_permissions';
    private const CACHE_KEY_ALL_ROLES = 'roles_permissions:all_roles';
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Clear all permission and role caches
     * Can be called manually if needed
     */
    public function clearCaches(): void
    {
        Cache::forget(self::CACHE_KEY_ALL_PERMISSIONS);
        Cache::forget(self::CACHE_KEY_ALL_ROLES);
        
        // Clear Spatie Permission's internal cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
    /**
     * Create a new permission
     *
     * @param string $name Permission name
     * @param string|null $guardName Guard name (default: 'web')
     * @return Permission
     * @throws \Exception
     */
    public function createPermission(string $name, ?string $guardName = 'web'): Permission
    {
        try {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guardName],
                ['name' => $name, 'guard_name' => $guardName]
            );
            
            // Clear cache when permission is created
            if ($permission->wasRecentlyCreated) {
                $this->clearCaches();
            }
            
            return $permission;
        } catch (\Exception $e) {
            Log::error('Failed to create permission: ' . $e->getMessage());
            throw new \Exception('Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Create multiple permissions at once
     *
     * @param array $permissions Array of permission names
     * @param string|null $guardName Guard name (default: 'web')
     * @return array Array of created permissions
     */
    public function createPermissions(array $permissions, ?string $guardName = 'web'): array
    {
        $created = [];
        foreach ($permissions as $permission) {
            $created[] = $this->createPermission($permission, $guardName);
        }
        return $created;
    }

    /**
     * Assign permission(s) to a user
     *
     * @param User $user User instance
     * @param string|array $permissions Permission name(s)
     * @return User
     */
    public function assignPermissionToUser(User $user, string|array $permissions): User
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            // Create permission if it doesn't exist
            $this->createPermission($permission);
        }

        $user->givePermissionTo($permissions);
        
        // Clear cache as permission assignment may affect cached data
        $this->clearCaches();
        
        return $user->fresh();
    }

    /**
     * Remove permission(s) from a user
     *
     * @param User $user User instance
     * @param string|array $permissions Permission name(s)
     * @return User
     */
    public function removePermissionFromUser(User $user, string|array $permissions): User
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        $user->revokePermissionTo($permissions);
        
        // Clear cache as permission removal may affect cached data
        $this->clearCaches();
        
        return $user->fresh();
    }

    /**
     * Check if user has a specific permission
     *
     * @param User $user User instance
     * @param string $permission Permission name
     * @return bool
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    /**
     * Check if user has any of the given permissions
     *
     * @param User $user User instance
     * @param array $permissions Array of permission names
     * @return bool
     */
    public function userHasAnyPermission(User $user, array $permissions): bool
    {
        return $user->hasAnyPermission($permissions);
    }

    /**
     * Check if user has all of the given permissions
     *
     * @param User $user User instance
     * @param array $permissions Array of permission names
     * @return bool
     */
    public function userHasAllPermissions(User $user, array $permissions): bool
    {
        return $user->hasAllPermissions($permissions);
    }

    /**
     * Get direct permissions assigned to a user (not from roles)
     * Returns only permissions from model_has_permissions table
     *
     * @param User $user User instance
     * @return \Illuminate\Support\Collection
     */
    public function getUserPermissions(User $user)
    {
        // Get only direct permissions (from model_has_permissions table)
        // Not permissions inherited from roles
        return $user->permissions;
    }

    /**
     * Create a new role
     *
     * @param string $name Role name
     * @param string|null $guardName Guard name (default: 'web')
     * @return Role
     * @throws \Exception
     */
    public function createRole(string $name, ?string $guardName = 'web'): Role
    {
        try {
            $role = Role::firstOrCreate(
                ['name' => $name, 'guard_name' => $guardName],
                ['name' => $name, 'guard_name' => $guardName]
            );
            
            // Clear cache when role is created
            if ($role->wasRecentlyCreated) {
                $this->clearCaches();
            }
            
            return $role;
        } catch (\Exception $e) {
            Log::error('Failed to create role: ' . $e->getMessage());
            throw new \Exception('Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Create role with permissions
     *
     * @param string $name Role name
     * @param array $permissions Array of permission names
     * @param string|null $guardName Guard name (default: 'web')
     * @return Role
     */
    public function createRoleWithPermissions(string $name, array $permissions, ?string $guardName = 'web'): Role
    {
        $role = $this->createRole($name, $guardName);
        
        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            $this->createPermission($permission, $guardName);
        }

        $role->givePermissionTo($permissions);
        
        // Clear cache (already cleared in createRole/createPermission, but ensure it's cleared)
        $this->clearCaches();
        
        return $role->fresh();
    }

    /**
     * Assign role(s) to a user
     *
     * @param User $user User instance
     * @param string|array $roles Role name(s)
     * @return User
     */
    public function assignRoleToUser(User $user, string|array $roles): User
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            // Create role if it doesn't exist
            $this->createRole($role);
        }

        $user->assignRole($roles);
        
        // Clear cache as role assignment may affect cached data
        $this->clearCaches();
        
        return $user->fresh();
    }

    /**
     * Remove role(s) from a user
     *
     * @param User $user User instance
     * @param string|array $roles Role name(s)
     * @return User
     */
    public function removeRoleFromUser(User $user, string|array $roles): User
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $user->removeRole($roles);
        
        // Clear cache as role removal may affect cached data
        $this->clearCaches();
        
        return $user->fresh();
    }

    /**
     * Sync roles for a user (removes all existing roles and assigns new ones)
     *
     * @param User $user User instance
     * @param array $roles Array of role names
     * @return User
     */
    public function syncUserRoles(User $user, array $roles): User
    {
        foreach ($roles as $role) {
            // Create role if it doesn't exist
            $this->createRole($role);
        }

        $user->syncRoles($roles);
        
        // Clear cache as role sync may affect cached data
        $this->clearCaches();
        
        return $user->fresh();
    }

    /**
     * Check if user has a specific role
     *
     * @param User $user User instance
     * @param string $role Role name
     * @return bool
     */
    public function userHasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Check if user has any of the given roles
     *
     * @param User $user User instance
     * @param array $roles Array of role names
     * @return bool
     */
    public function userHasAnyRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has all of the given roles
     *
     * @param User $user User instance
     * @param array $roles Array of role names
     * @return bool
     */
    public function userHasAllRoles(User $user, array $roles): bool
    {
        return $user->hasAllRoles($roles);
    }

    /**
     * Get all roles for a user
     *
     * @param User $user User instance
     * @return \Illuminate\Support\Collection
     */
    public function getUserRoles(User $user)
    {
        return $user->roles;
    }

    /**
     * Assign permission(s) to a role
     *
     * @param string|Role $role Role name or Role instance
     * @param string|array $permissions Permission name(s)
     * @return Role
     */
    public function assignPermissionToRole(string|Role $role, string|array $permissions): Role
    {
        if (is_string($role)) {
            $role = $this->createRole($role);
        }

        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            $this->createPermission($permission);
        }

        $role->givePermissionTo($permissions);
        
        // Clear cache as role-permission assignment changes relationships
        $this->clearCaches();
        
        return $role->fresh();
    }

    /**
     * Remove permission(s) from a role
     *
     * @param string|Role $role Role name or Role instance
     * @param string|array $permissions Permission name(s)
     * @return Role
     */
    public function removePermissionFromRole(string|Role $role, string|array $permissions): Role
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        $role->revokePermissionTo($permissions);
        
        // Clear cache as role-permission removal changes relationships
        $this->clearCaches();
        
        return $role->fresh();
    }

    /**
     * Get all permissions for a role
     *
     * @param string|Role $role Role name or Role instance
     * @return \Illuminate\Support\Collection
     */
    public function getRolePermissions(string|Role $role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        return $role->permissions;
    }

    /**
     * Get all roles (cached)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllRoles()
    {
        return Cache::remember(self::CACHE_KEY_ALL_ROLES, self::CACHE_TTL, function () {
            return Role::all();
        });
    }

    /**
     * Get all permissions (cached)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        return Cache::remember(self::CACHE_KEY_ALL_PERMISSIONS, self::CACHE_TTL, function () {
            return Permission::all();
        });
    }

    /**
     * Delete a permission
     *
     * @param string|Permission $permission Permission name or Permission instance
     * @return bool
     * @throws \Exception
     */
    public function deletePermission(string|Permission $permission): bool
    {
        try {
            if (is_string($permission)) {
                $permission = Permission::where('name', $permission)->firstOrFail();
            }

            $deleted = $permission->delete();
            
            // Clear cache when permission is deleted
            if ($deleted) {
                $this->clearCaches();
            }
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete permission: ' . $e->getMessage());
            throw new \Exception('Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Delete a role
     *
     * @param string|Role $role Role name or Role instance
     * @return bool
     * @throws \Exception
     */
    public function deleteRole(string|Role $role): bool
    {
        try {
            if (is_string($role)) {
                $role = Role::where('name', $role)->firstOrFail();
            }

            $deleted = $role->delete();
            
            // Clear cache when role is deleted
            if ($deleted) {
                $this->clearCaches();
            }
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete role: ' . $e->getMessage());
            throw new \Exception('Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Sync permissions for a role (removes all existing permissions and assigns new ones)
     *
     * @param string|Role $role Role name or Role instance
     * @param array $permissions Array of permission names
     * @return Role
     */
    public function syncRolePermissions(string|Role $role, array $permissions): Role
    {
        if (is_string($role)) {
            $role = $this->createRole($role);
        }

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            $this->createPermission($permission);
        }

        $role->syncPermissions($permissions);
        
        // Clear cache as permission sync changes relationships
        $this->clearCaches();
        
        return $role->fresh();
    }

    /**
     * Sync permissions for a user (removes all existing permissions and assigns new ones)
     *
     * @param User $user User instance
     * @param array $permissions Array of permission names
     * @return User
     */
    public function syncUserPermissions(User $user, array $permissions): User
    {
        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            $this->createPermission($permission);
        }

        $user->syncPermissions($permissions);
        
        // Clear cache as permission sync may affect cached data
        $this->clearCaches();
        
        return $user->fresh();
    }
}

