<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanAvailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class PlanAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $plans = Plan::all();
            $dealerRole = Role::where('name', 'dealer')->first();
            $userRole = Role::where('name', 'user')->first();
            
            if ($plans->isEmpty() || !$dealerRole) {
                return;
            }
            
            foreach ($plans as $plan) {
                // All plans available to dealers
                PlanAvailability::firstOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'allowed_role_id' => $dealerRole->id,
                    ],
                    [
                        'is_enabled' => true,
                        'created_at' => now(),
                    ]
                );
                
                // Trial plan also available to regular users
                if ($plan->slug === 'trial' && $userRole) {
                    PlanAvailability::firstOrCreate(
                        [
                            'plan_id' => $plan->id,
                            'allowed_role_id' => $userRole->id,
                        ],
                        [
                            'is_enabled' => true,
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });
    }
}

