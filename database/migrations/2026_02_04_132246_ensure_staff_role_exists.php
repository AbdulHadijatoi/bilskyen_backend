<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\RolePermissionService;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensures "staff" role exists in roles table
     * Also ensures "dealer" and "admin" roles exist for consistency
     */
    public function up(): void
    {
        $rolePermissionService = app(RolePermissionService::class);
        
        // Clear cache first
        $rolePermissionService->clearCaches();

        // Ensure "staff" role exists
        $rolePermissionService->createRole('staff');
        
        // Ensure "dealer" role exists (for consistency)
        $rolePermissionService->createRole('dealer');
        
        // Ensure "admin" role exists (for consistency)
        $rolePermissionService->createRole('admin');
    }

    /**
     * Reverse the migrations.
     * Note: We don't delete roles in down() as they may be in use
     */
    public function down(): void
    {
        // Do nothing - roles should not be deleted as they may be in use
    }
};
