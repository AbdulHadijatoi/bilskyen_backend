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

        $rolePermissionService->createPermission('dealer.analytics.view', 'web');

        $dealerRole = Role::where('name', 'dealer')->where('guard_name', 'web')->first();
        if ($dealerRole) {
            $dealerRole->givePermissionTo('dealer.analytics.view');
        }

        Cache::forget('permissions:all_items');
        $rolePermissionService->clearCaches();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'dealer.analytics.view')->where('guard_name', 'web')->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->users()->detach();
            $permission->delete();
        }

        Cache::forget('permissions:all_items');
        app(RolePermissionService::class)->clearCaches();
    }
};
