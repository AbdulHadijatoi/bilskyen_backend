<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\RolePermissionService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // lets truncate first and then add records to avoid duplicates
        // truncate roles and permissions tables
        // disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Role::truncate();
        Permission::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $rolePermissionService = app(RolePermissionService::class);
        
        // Clear cache first
        $rolePermissionService->clearCaches();

        // Define all permissions grouped by entity
        $permissions = [
            // Dashboard permissions
            'dashboard.view',
            
            // Vehicle permissions
            'vehicle.list',
            'vehicle.view',
            'vehicle.create',
            'vehicle.update',
            'vehicle.delete',
            
            // Contact permissions
            'contact.list',
            'contact.view',
            'contact.create',
            'contact.update',
            'contact.delete',
            
            // Enquiry permissions
            'enquiry.list',
            'enquiry.view',
            'enquiry.create',
            'enquiry.update',
            'enquiry.delete',
            
            // Purchase permissions
            'purchase.list',
            'purchase.view',
            'purchase.create',
            'purchase.update',
            'purchase.delete',
            
            // Sale permissions
            'sale.list',
            'sale.view',
            'sale.create',
            'sale.update',
            'sale.delete',
            
            // Expense permissions
            'expense.list',
            'expense.view',
            'expense.create',
            'expense.update',
            'expense.delete',
            
            // Financial Account permissions
            'financial-account.list',
            'financial-account.view',
            'financial-account.create',
            'financial-account.update',
            'financial-account.delete',
            
            // Transaction permissions
            'transaction.list',
            'transaction.view',
            'transaction.create',
            'transaction.update',
            'transaction.delete',
            
            // Notification permissions
            'notification.list',
            'notification.view',
            'notification.create',
            'notification.update',
            'notification.delete',
            
            // File permissions
            'files.upload',
            'files.delete',
            
            // User permissions (for admin)
            'user.list',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            
            // Permission management (for admin)
            'permission.list',
            'permission.view',
            'permission.create',
            'permission.update',
            'permission.delete',
            'permission.assign',
            
            // Role management (for admin)
            'role.list',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
            'role.assign',
        ];

        // Create all permissions
        $this->command->info('Creating permissions...');
        foreach ($permissions as $permission) {
            $rolePermissionService->createPermission($permission);
        }
        $this->command->info('✓ Permissions created');

        // Create roles (using lowercase names to match codebase expectations)
        $this->command->info('Creating roles...');
        
        // User role (minimal permissions, previously called Buyer)
        $userRole = $rolePermissionService->createRole('user');
        $userPermissions = [
            'dashboard.view',
            'vehicle.list',
            'vehicle.view',
            'contact.view',
            'enquiry.create',
        ];
        $rolePermissionService->syncRolePermissions($userRole, $userPermissions);
        $this->command->info('✓ User role created with permissions');

        // Dealer role (most permissions for managing dealership)
        $dealerRole = $rolePermissionService->createRole('dealer');
        $dealerPermissions = [
            'dashboard.view',
            'vehicle.list',
            'vehicle.view',
            'vehicle.create',
            'vehicle.update',
            'vehicle.delete',
            'contact.list',
            'contact.view',
            'contact.create',
            'contact.update',
            'contact.delete',
            'enquiry.list',
            'enquiry.view',
            'enquiry.create',
            'enquiry.update',
            'enquiry.delete',
            'purchase.list',
            'purchase.view',
            'purchase.create',
            'purchase.update',
            'purchase.delete',
            'sale.list',
            'sale.view',
            'sale.create',
            'sale.update',
            'sale.delete',
            'expense.list',
            'expense.view',
            'expense.create',
            'expense.update',
            'expense.delete',
            'financial-account.list',
            'financial-account.view',
            'financial-account.create',
            'financial-account.update',
            'financial-account.delete',
            'transaction.list',
            'transaction.view',
            'transaction.create',
            'transaction.update',
            'transaction.delete',
            'notification.list',
            'notification.view',
            'notification.create',
            'notification.update',
            'files.upload',
            'files.delete',
        ];
        $rolePermissionService->syncRolePermissions($dealerRole, $dealerPermissions);
        $this->command->info('✓ Dealer role created with permissions');

        // Admin role (all permissions)
        $adminRole = $rolePermissionService->createRole('admin');
        $rolePermissionService->syncRolePermissions($adminRole, $permissions);
        $this->command->info('✓ Admin role created with all permissions');

        $this->command->info('Roles and permissions seeding completed!');
    }
}

