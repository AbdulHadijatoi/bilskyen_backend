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
            $sellerRole = Role::where('name', 'seller')->first();
            
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
                
                // Trial plan also available to sellers
                if ($plan->slug === 'trial' && $sellerRole) {
                    PlanAvailability::firstOrCreate(
                        [
                            'plan_id' => $plan->id,
                            'allowed_role_id' => $sellerRole->id,
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

