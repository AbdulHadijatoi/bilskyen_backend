<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\Dealer;
use App\Models\User;
use App\Models\DealerUser;
use App\Models\Location;
use App\Models\FuelType;
use App\Models\Transmission;
use App\Models\VehicleListStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create('da_DK');
            
            $dealers = Dealer::all();
            $users = User::whereHas('dealers')->get();
            $locations = Location::all();
            $fuelTypes = FuelType::all();
            $transmissions = Transmission::all();
            
            if ($dealers->isEmpty() || $users->isEmpty() || $locations->isEmpty()) {
                $this->command->warn('Required data not found. Please run previous seeders first.');
                return;
            }
            
            $bodyTypes = ['Sedan', 'SUV', 'Hatchback', 'Coupe', 'Convertible', 'Wagon', 'Van', 'Pickup'];
            $makes = ['Volvo', 'BMW', 'Mercedes-Benz', 'Audi', 'VW', 'Toyota', 'Ford', 'Peugeot', 'Opel', 'Skoda'];
            $models = ['Model A', 'Model B', 'Model C', 'Model X', 'Model Y'];
            
            for ($i = 0; $i < 30; $i++) {
                $dealer = $dealers->random();
                // Get users associated with this dealer
                $dealerUserIds = DealerUser::where('dealer_id', $dealer->id)->pluck('user_id');
                $dealerUsers = $users->whereIn('id', $dealerUserIds);
                $user = $dealerUsers->isNotEmpty() ? $dealerUsers->random() : $users->random();
                $location = $locations->random();
                $fuelType = $fuelTypes->random();
                $transmission = $transmissions->random();
                
                $year = $faker->numberBetween(2015, 2024);
                $mileage = $faker->numberBetween(10000, 200000);
                $price = $faker->numberBetween(50000, 500000); // DKK
                
                $make = $faker->randomElement($makes);
                $model = $faker->randomElement($models);
                $title = "{$year} {$make} {$model}";
                
                $statusId = $faker->randomElement([
                    VehicleListStatus::DRAFT,
                    VehicleListStatus::PUBLISHED,
                    VehicleListStatus::PUBLISHED,
                    VehicleListStatus::PUBLISHED,
                    VehicleListStatus::SOLD,
                    VehicleListStatus::ARCHIVED,
                ]);
                
                $publishedAt = $statusId === VehicleListStatus::PUBLISHED 
                    ? $faker->dateTimeBetween('-6 months', 'now')
                    : null;
                
                Vehicle::create([
                    'dealer_id' => $dealer->id,
                    'user_id' => $user->id,
                    'location_id' => $location->id,
                    'title' => $title,
                    'description' => $faker->paragraph(5),
                    'price' => $price,
                    'mileage' => $mileage,
                    'year' => $year,
                    'fuel_type_id' => $fuelType->id,
                    'transmission_id' => $transmission->id,
                    'body_type' => $faker->randomElement($bodyTypes),
                    'has_carplay' => $faker->boolean(60),
                    'has_adaptive_cruise' => $faker->boolean(40),
                    'is_electric' => $fuelType->name === 'Electric',
                    'specs' => [
                        'engine' => $faker->numberBetween(1000, 3000) . 'cc',
                        'power' => $faker->numberBetween(100, 300) . 'hp',
                        'seats' => $faker->numberBetween(4, 7),
                    ],
                    'equipment' => $faker->randomElements(
                        ['Navigation', 'Leather Seats', 'Sunroof', 'Parking Sensors', 'Backup Camera', 'Bluetooth'],
                        $faker->numberBetween(2, 5)
                    ),
                    'vehicle_list_status_id' => $statusId,
                    'published_at' => $publishedAt,
                    'views_count' => $statusId === VehicleListStatus::PUBLISHED 
                        ? $faker->numberBetween(0, 500)
                        : 0,
                ]);
            }
        });
    }
}

