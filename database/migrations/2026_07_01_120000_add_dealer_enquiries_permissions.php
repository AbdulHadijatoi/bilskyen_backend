<?php

use App\Services\RolePermissionService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $rolePermissionService = app(RolePermissionService::class);

        $permissions = [
            'dealer.enquiries.view',
            'dealer.enquiries.update',
        ];

        foreach ($permissions as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }

        $dealerRole = Role::where('name', 'dealer')->where('guard_name', 'web')->first();
        if ($dealerRole) {
            $dealerRole->givePermissionTo($permissions);
        }

        $rolePermissionService->clearCaches();
    }

    public function down(): void
    {
        $permissions = Permission::whereIn('name', [
            'dealer.enquiries.view',
            'dealer.enquiries.update',
        ])->where('guard_name', 'web')->get();

        foreach ($permissions as $permission) {
            $permission->delete();
        }

        app(RolePermissionService::class)->clearCaches();
    }
};
