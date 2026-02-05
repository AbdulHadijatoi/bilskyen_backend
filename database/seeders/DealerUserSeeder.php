<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\DealerStaff;
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
            $sellerRole = Role::where('name', 'seller')->first();
            
            if (!$dealerRole || !$sellerRole) {
                $this->command->warn('Roles not found. Please run RolesAndPermissionsSeeder first.');
                return;
            }
            
            // Link users to dealers
            $userIndex = 0;
            foreach ($dealers as $dealer) {
                // Set first user as dealer owner (if not already set)
                if (!$dealer->user_id && $userIndex < $users->count()) {
                    $owner = $users[$userIndex];
                    $dealer->update(['user_id' => $owner->id]);
                    
                    // Assign dealer role to owner if not already assigned
                    if (!$owner->hasRole('dealer')) {
                        $owner->assignRole('dealer');
                    }
                    
                    $userIndex++;
                }
                
                // Each dealer gets 1-3 additional staff members
                $staffCount = rand(1, 3);
                
                for ($i = 0; $i < $staffCount && $userIndex < $users->count(); $i++) {
                    $user = $users[$userIndex];
                    
                    // Generate username for staff
                    $username = $this->generateUsername($dealer->id, $i + 1);
                    
                    // Create dealer staff record
                    DealerStaff::firstOrCreate(
                        [
                            'dealer_id' => $dealer->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'username' => $username,
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
            
            // Assign seller role to remaining users (buyers)
            $remainingUsers = $users->skip($userIndex);
            foreach ($remainingUsers as $user) {
                if (!$user->hasRole('seller')) {
                    $user->assignRole('seller');
                }
            }
        });
    }

    /**
     * Generate username for staff member
     * Format: staff_{dealer_id}_{sequential_number}
     */
    private function generateUsername(int $dealerId, int $number): string
    {
        return sprintf('staff_%d_%03d', $dealerId, $number);
    }
}
