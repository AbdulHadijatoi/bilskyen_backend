<?php

namespace Database\Seeders;

use App\Models\Favorite;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })->get();
            
            $vehicles = Vehicle::where('vehicle_list_status_id', \App\Models\VehicleListStatus::PUBLISHED)->get();
            
            if ($users->isEmpty() || $vehicles->isEmpty()) {
                return;
            }
            
            // Create favorites for some users
            foreach ($users->take(10) as $user) {
                // Each user favorites 2-5 vehicles
                $favoriteCount = $faker->numberBetween(2, 5);
                $userVehicles = $vehicles->random($favoriteCount);
                
                foreach ($userVehicles as $vehicle) {
                    Favorite::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'vehicle_id' => $vehicle->id,
                        ],
                        [
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });
    }
}

