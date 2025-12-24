<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\DealerUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DealerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $dealers = Dealer::all();
            $users = User::where('email', '!=', 'admin@example.com')->get();
            
            // Get roles
            $dealerRole = Role::where('name', 'dealer')->first();
            $userRole = Role::where('name', 'user')->first();
            
            if (!$dealerRole || !$userRole) {
                $this->command->warn('Roles not found. Please run RolesAndPermissionsSeeder first.');
                return;
            }
            
            // Link some users to dealers
            $userIndex = 0;
            foreach ($dealers as $dealer) {
                // Each dealer gets 1-3 staff members
                $staffCount = rand(1, 3);
                
                for ($i = 0; $i < $staffCount && $userIndex < $users->count(); $i++) {
                    $user = $users[$userIndex];
                    
                    // First user is owner, others are manager or staff
                    $roleId = $i === 0 
                        ? DealerUser::ROLE_OWNER 
                        : ($i === 1 ? DealerUser::ROLE_MANAGER : DealerUser::ROLE_STAFF);
                    
                    DealerUser::firstOrCreate(
                        [
                            'dealer_id' => $dealer->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'role_id' => $dealerRole->id, // Spatie role ID
                            'created_at' => now()->subDays(rand(30, 365)),
                        ]
                    );
                    
                    // Assign dealer role to user if not already assigned
                    if (!$user->hasRole('dealer')) {
                        $user->assignRole('dealer');
                    }
                    
                    $userIndex++;
                }
            }
            
            // Assign user role to remaining users (buyers)
            $remainingUsers = $users->skip($userIndex);
            foreach ($remainingUsers as $user) {
                if (!$user->hasRole('user')) {
                    $user->assignRole('user');
                }
            }
        });
    }
}

