<?php

namespace Database\Seeders;

use App\Models\PriceHistory;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PriceHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $vehicles = Vehicle::all();
            $dealerStaff = User::whereHas('dealers')->get();
            
            if ($vehicles->isEmpty() || $dealerStaff->isEmpty()) {
                return;
            }
            
            // Create price history for some vehicles
            foreach ($vehicles->take(15) as $vehicle) {
                $changedBy = $dealerStaff->random();
                
                // Create 1-3 price changes per vehicle
                $changeCount = $faker->numberBetween(1, 3);
                $currentPrice = $vehicle->price;
                
                for ($i = 0; $i < $changeCount; $i++) {
                    $oldPrice = $i === 0 
                        ? $currentPrice + $faker->numberBetween(10000, 50000) 
                        : $currentPrice;
                    
                    $newPrice = $oldPrice - $faker->numberBetween(5000, 30000);
                    
                    PriceHistory::create([
                        'vehicle_id' => $vehicle->id,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'changed_by_user_id' => $changedBy->id,
                        'changed_at' => $faker->dateTimeBetween($vehicle->created_at, 'now'),
                    ]);
                    
                    $currentPrice = $newPrice;
                }
            }
        });
    }
}

