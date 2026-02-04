<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\RolePermissionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class DealerStaffRolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates dealer-specific permissions
     * All staff members get "staff" role (single role for all staff)
     * Admin vs staff distinctions handled via direct permission assignments
     */
    public function run(): void
    {
        $rolePermissionService = app(RolePermissionService::class);
        
        // Clear cache first
        $rolePermissionService->clearCaches();

        // Define dealer-specific permissions
        $dealerPermissions = [
            // Vehicle permissions (dealer-scoped)
            'dealer.vehicles.view',
            'dealer.vehicles.create',
            'dealer.vehicles.update',
            'dealer.vehicles.delete',
            'dealer.vehicles.media',      // Upload/manage images
            'dealer.vehicles.status',     // Change status (draft/ready/reserved)
            'dealer.vehicles.sold',       // Mark as sold (admin only)
            
            // Lead permissions (dealer-scoped)
            'dealer.leads.view',
            'dealer.leads.update',
            'dealer.leads.assign',
            'dealer.leads.notes',         // Add internal notes
            'dealer.leads.messages',      // Send messages/communicate
            
            // Staff management (admin only)
            'dealer.staff.manage',
            
            // Audit logs (admin only)
            'dealer.audit.view',
            
            // Subscription management (admin only)
            'dealer.subscription.manage',
            
            // Dashboard/reporting
            'dealer.dashboard.view',
        ];

        // Create all dealer permissions
        $this->command->info('Creating dealer permissions...');
        foreach ($dealerPermissions as $permission) {
            $rolePermissionService->createPermission($permission);
        }
        $this->command->info('✓ Dealer permissions created');

        // Ensure 'dealer', 'staff', and 'admin' roles exist
        // All staff members (owners, managers, staff) get "staff" role
        // Admin capabilities determined by direct permission assignments
        $this->command->info('Ensuring roles exist...');
        
        $rolePermissionService->createRole('dealer');
        $rolePermissionService->createRole('staff');
        $rolePermissionService->createRole('admin');
        
        $this->command->info('✓ Roles ensured (dealer, staff, admin)');
        $this->command->info('Note: All staff members get "staff" role. Permissions are assigned directly to users.');

        $this->command->info('Dealer staff permissions seeding completed!');
    }
}
