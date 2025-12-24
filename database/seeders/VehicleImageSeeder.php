<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class VehicleImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $vehicles = Vehicle::all();
            
            foreach ($vehicles as $vehicle) {
                // Each vehicle gets 3-8 images
                $imageCount = $faker->numberBetween(3, 8);
                
                for ($i = 0; $i < $imageCount; $i++) {
                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => "vehicles/{$vehicle->id}/image-" . ($i + 1) . ".jpg",
                        'sort_order' => $i + 1,
                    ]);
                }
            }
        });
    }
}

