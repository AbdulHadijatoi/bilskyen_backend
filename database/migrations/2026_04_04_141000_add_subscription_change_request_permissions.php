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

        $rolePermissionService->createPermission('admin.subscription_change_requests.view', 'web');
        $rolePermissionService->createPermission('admin.subscription_change_requests.review', 'web');

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'admin.subscription_change_requests.view',
                'admin.subscription_change_requests.review',
            ]);
        }

        Cache::forget('permissions:all_items');
        $rolePermissionService->clearCaches();
    }

    public function down(): void
    {
        $names = [
            'admin.subscription_change_requests.view',
            'admin.subscription_change_requests.review',
        ];

        foreach ($names as $name) {
            $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if ($permission) {
                $permission->roles()->detach();
                $permission->users()->detach();
                $permission->delete();
            }
        }

        Cache::forget('permissions:all_items');
        app(RolePermissionService::class)->clearCaches();
    }
};
