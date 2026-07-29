<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use App\Services\RolePermissionService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $rolePermissionService = app(RolePermissionService::class);

        $rolePermissionService->createPermission('admin.leads.view', 'web');

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo('admin.leads.view');
        }

        Cache::forget('permissions:all_items');
        $rolePermissionService->clearCaches();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'admin.leads.view')->where('guard_name', 'web')->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->users()->detach();
            $permission->delete();
        }

        Cache::forget('permissions:all_items');
        app(RolePermissionService::class)->clearCaches();
    }
};
