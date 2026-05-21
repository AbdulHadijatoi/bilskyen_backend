<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\RolePermissionService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate permissions and related pivot tables
        DB::table($tableNames['permissions'])->truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        
        $rolePermissionService = app(RolePermissionService::class);
        
        // Clear cache first
        $rolePermissionService->clearCaches();
        
        // Define all dealer permissions from dealer-apis.php
        $dealerPermissions = [
            'dealer.dashboard.view',
            'dealer.vehicles.view',
            'dealer.vehicles.create',
            'dealer.vehicles.update',
            'dealer.vehicles.delete',
            'dealer.vehicles.status',
            'dealer.vehicles.media',
            'dealer.leads.view',
            'dealer.leads.assign',
            'dealer.leads.update',
            'dealer.leads.messages',
            'dealer.staff.manage',
            'dealer.subscription.manage',
            'dealer.audit.view',
            'dealer.analytics.view',
        ];
        
        // Define all admin permissions from admin-apis.php
        $adminPermissions = [
            // Dashboard
            'admin.dashboard.view',
            
            // User Management
            'admin.users.view',
            'admin.users.create',
            'admin.users.update',
            'admin.users.delete',
            'admin.users.update-status',
            'admin.users.change-password',
            'admin.users.ban',
            'admin.users.unban',
            
            // Vehicle Management
            'admin.vehicles.view',
            'admin.vehicles.update',
            'admin.vehicles.update-status',
            'admin.vehicles.update-images',
            'admin.vehicles.delete-image',
            'admin.vehicles.update-equipment',
            'admin.vehicles.delete',
            
            // Plan Management
            'admin.plans.view',
            'admin.plans.create',
            'admin.plans.update',
            'admin.plans.delete',
            'admin.plans.assign-feature',
            'admin.plans.remove-feature',
            'admin.plans.sync-availability',
            'admin.plans.update-pricing',
            
            // Subscription Management
            'admin.subscriptions.view',
            'admin.subscriptions.create',
            'admin.subscriptions.update',
            'admin.subscriptions.update-status',
            'admin.subscriptions.cancel',
            'admin.subscriptions.renew',
            'admin.subscription_change_requests.view',
            'admin.subscription_change_requests.review',
            
            // Dealer Management
            'admin.dealers.view',
            
            // Feature Management
            'admin.features.view',
            'admin.features.create',
            'admin.features.update',
            'admin.features.delete',
            
            // CMS Management
            'admin.pages.view',
            'admin.pages.create',
            'admin.pages.update',
            'admin.pages.delete',
            'admin.pages.publish',
            
            // Featured Vehicles
            'admin.featured-vehicles.view',
            'admin.featured-vehicles.create',
            'admin.featured-vehicles.update',
            'admin.featured-vehicles.delete',
            
            // Analytics
            'admin.analytics.view',
            
            // Audit Logs
            'admin.audit-logs.view',
            
            // Notifications
            'admin.notifications.view',
            
            // Permission Management
            'admin.permissions.view',
            'admin.permissions.assign',
            'admin.permissions.revoke',
            
            // Constants
            'admin.constants.view',
            
            // Constants Management - Brands
            'admin.brands.view',
            'admin.brands.create',
            'admin.brands.update',
            'admin.brands.delete',
            
            // Constants Management - Model Years
            'admin.model-years.view',
            'admin.model-years.create',
            'admin.model-years.update',
            'admin.model-years.delete',
            
            // Constants Management - Fuel Types
            'admin.fuel-types.view',
            'admin.fuel-types.create',
            'admin.fuel-types.update',
            'admin.fuel-types.delete',
            
            // Constants Management - Gear Types
            'admin.gear-types.view',
            'admin.gear-types.create',
            'admin.gear-types.update',
            'admin.gear-types.delete',
            
            // Constants Management - Listing Types
            'admin.listing-types.view',
            'admin.listing-types.create',
            'admin.listing-types.update',
            'admin.listing-types.delete',
            
            // Constants Management - Body Types
            'admin.body-types.view',
            'admin.body-types.create',
            'admin.body-types.update',
            'admin.body-types.delete',
            
            // Constants Management - Colors
            'admin.colors.view',
            'admin.colors.create',
            'admin.colors.update',
            'admin.colors.delete',
            
            // Constants Management - Variants
            'admin.variants.view',
            'admin.variants.create',
            'admin.variants.update',
            'admin.variants.delete',
            
            // Constants Management - Types
            'admin.types.view',
            'admin.types.create',
            'admin.types.update',
            'admin.types.delete',
            
            // Constants Management - Conditions
            'admin.conditions.view',
            'admin.conditions.create',
            'admin.conditions.update',
            'admin.conditions.delete',
            
            // Constants Management - Sales Types
            'admin.sales-types.view',
            'admin.sales-types.create',
            'admin.sales-types.update',
            'admin.sales-types.delete',
            
            // Constants Management - Price Types
            'admin.price-types.view',
            'admin.price-types.create',
            'admin.price-types.update',
            'admin.price-types.delete',
            
            // Constants Management - Euronorms
            'admin.euronorms.view',
            'admin.euronorms.create',
            'admin.euronorms.update',
            'admin.euronorms.delete',
            
            // Constants Management - Vehicle Models
            'admin.vehicle-models.view',
            'admin.vehicle-models.create',
            'admin.vehicle-models.update',
            'admin.vehicle-models.delete',
            
            // Constants Management - Vehicle Uses
            'admin.vehicle-uses.view',
            'admin.vehicle-uses.create',
            'admin.vehicle-uses.update',
            'admin.vehicle-uses.delete',
            
            // Constants Management - Vehicle List Statuses
            'admin.vehicle-list-statuses.view',
            'admin.vehicle-list-statuses.create',
            'admin.vehicle-list-statuses.update',
            'admin.vehicle-list-statuses.delete',
            
            // Constants Management - Equipment Types
            'admin.equipment-types.view',
            'admin.equipment-types.create',
            'admin.equipment-types.update',
            'admin.equipment-types.delete',
            
            // Constants Management - Equipments
            'admin.equipments.view',
            'admin.equipments.create',
            'admin.equipments.update',
            'admin.equipments.delete',
        ];
        
        // Create all dealer permissions
        foreach ($dealerPermissions as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }
        
        // Create all admin permissions
        foreach ($adminPermissions as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }
        
        // Ensure dealer and admin roles exist
        $dealerRole = Role::where('name', 'dealer')->where('guard_name', 'web')->first();
        if (!$dealerRole) {
            $dealerRole = $rolePermissionService->createRole('dealer', 'web');
        }
        
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if (!$adminRole) {
            $adminRole = $rolePermissionService->createRole('admin', 'web');
        }
        
        // Assign all dealer permissions to dealer role
        $rolePermissionService->syncRolePermissions($dealerRole, $dealerPermissions);
        
        // Assign all admin permissions to admin role
        $rolePermissionService->syncRolePermissions($adminRole, $adminPermissions);
        
        // Clear cache after all operations
        $rolePermissionService->clearCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate permissions and related pivot tables
        DB::table($tableNames['role_has_permissions'])->truncate();
        DB::table($tableNames['model_has_permissions'])->truncate();
        DB::table($tableNames['permissions'])->truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        
        // Clear cache
        $rolePermissionService = app(RolePermissionService::class);
        $rolePermissionService->clearCaches();
    }
};
