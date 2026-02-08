<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use App\Services\RolePermissionService;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rolePermissionService = app(RolePermissionService::class);
        
        // Create vehicle.feature permission
        $rolePermissionService->createPermission('vehicle.seller.feature', 'web');
        
        // Clear permissions cache from PermissionManagementController
        Cache::forget('permissions:all_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the permission
        $permission = Permission::where('name', 'vehicle.seller.feature')
            ->where('guard_name', 'web')
            ->first();
            
        if ($permission) {
            // Remove from all roles first
            $permission->roles()->detach();
            // Remove from all users
            $permission->users()->detach();
            // Delete the permission
            $permission->delete();
            
            // Clear permissions cache
            Cache::forget('permissions:all_items');
            app(RolePermissionService::class)->clearCaches();
        }
    }
};
