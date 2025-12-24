<?php

namespace Database\Seeders;

use App\Models\ListingViewsLog;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ListingViewsLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $vehicles = Vehicle::where('vehicle_list_status_id', \App\Models\VehicleListStatus::PUBLISHED)->get();
            $users = User::all();
            
            if ($vehicles->isEmpty()) {
                return;
            }
            
            // Create view logs for published vehicles
            foreach ($vehicles as $vehicle) {
                // Each vehicle gets 5-30 views
                $viewCount = $faker->numberBetween(5, 30);
                
                for ($i = 0; $i < $viewCount; $i++) {
                    $user = $faker->boolean(40) ? $users->random() : null; // 40% logged in users
                    
                    ListingViewsLog::create([
                        'vehicle_id' => $vehicle->id,
                        'user_id' => $user?->id,
                        'ip_address' => $faker->ipv4(),
                        'user_agent' => $faker->userAgent(),
                        'viewed_at' => $faker->dateTimeBetween($vehicle->published_at ?? $vehicle->created_at, 'now'),
                    ]);
                }
            }
        });
    }
}

