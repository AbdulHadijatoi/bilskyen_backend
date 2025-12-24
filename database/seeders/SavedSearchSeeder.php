<?php

namespace Database\Seeders;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SavedSearchSeeder extends Seeder
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
            
            if ($users->isEmpty()) {
                return;
            }
            
            // Create saved searches for some users
            foreach ($users->take(8) as $user) {
                // Each user has 1-3 saved searches
                $searchCount = $faker->numberBetween(1, 3);
                
                for ($i = 0; $i < $searchCount; $i++) {
                    SavedSearch::create([
                        'user_id' => $user->id,
                        'filters' => [
                            'min_price' => $faker->optional()->numberBetween(50000, 200000),
                            'max_price' => $faker->optional()->numberBetween(200000, 500000),
                            'min_year' => $faker->optional()->numberBetween(2018, 2023),
                            'max_year' => $faker->optional()->numberBetween(2023, 2024),
                            'fuel_type' => $faker->optional()->randomElement(['Petrol', 'Diesel', 'Electric']),
                            'body_type' => $faker->optional()->randomElement(['Sedan', 'SUV', 'Hatchback']),
                            'max_mileage' => $faker->optional()->numberBetween(50000, 150000),
                        ],
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }
}

