<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\RolePermissionService;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, migrate existing role data to roles relationship
        $rolePermissionService = app(RolePermissionService::class);
        
        // Ensure roles exist (create if they don't)
        $roleMap = [
            'user' => 'user',
            'dealer' => 'dealer',
            'admin' => 'admin',
        ];
        
        foreach ($roleMap as $oldRole => $roleName) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['name' => $roleName, 'guard_name' => 'web']
            );
        }
        
        // Assign roles to users based on their role column
        $users = User::whereNotNull('role')->get();
        foreach ($users as $user) {
            $roleName = strtolower($user->role);
            if (in_array($roleName, ['user', 'dealer', 'admin'])) {
                // Only assign if user doesn't already have roles
                if ($user->roles->isEmpty()) {
                    $rolePermissionService->assignRoleToUser($user, $roleName);
                }
            }
        }
        
        // Drop the index on role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
        
        // Drop CHECK constraint if not SQLite
        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_role');
            } catch (\Exception $e) {
                // Constraint might not exist, ignore
            }
        }
        
        // Drop the role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('user')->after('address');
        });
        
        // Restore role data from roles relationship
        $users = User::with('roles')->get();
        foreach ($users as $user) {
            $roleName = 'user'; // default
            if ($user->roles->isNotEmpty()) {
                // Get the first role name (or prioritize admin > dealer > user)
                $roleNames = $user->roles->pluck('name')->map('strtolower')->toArray();
                if (in_array('admin', $roleNames)) {
                    $roleName = 'admin';
                } elseif (in_array('dealer', $roleNames)) {
                    $roleName = 'dealer';
                } elseif (in_array('user', $roleNames)) {
                    $roleName = 'user';
                } else {
                    $roleName = $roleNames[0] ?? 'user';
                }
            }
            $user->update(['role' => $roleName]);
        }
        
        // Recreate index
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
        
        // Recreate CHECK constraint if not SQLite
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN (\'user\', \'dealer\', \'admin\'))');
        }
    }
};
