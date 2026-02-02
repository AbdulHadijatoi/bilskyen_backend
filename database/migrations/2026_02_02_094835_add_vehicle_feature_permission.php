<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        $rolePermissionService->createPermission('vehicle.feature', 'web');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the permission
        $permission = Permission::where('name', 'vehicle.feature')
            ->where('guard_name', 'web')
            ->first();
            
        if ($permission) {
            // Remove from all roles first
            $permission->roles()->detach();
            // Remove from all users
            $permission->users()->detach();
            // Delete the permission
            $permission->delete();
        }
    }
};
