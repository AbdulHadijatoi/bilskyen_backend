<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Feature;
use App\Models\UserPlanOverride;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UserPlanOverrideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $users = User::whereHas('dealers')->take(5)->get();
            $features = Feature::all();
            
            if ($users->isEmpty() || $features->isEmpty()) {
                return;
            }
            
            // Create overrides for a few users
            foreach ($users as $user) {
                // Give 1-2 feature overrides per user
                $overrideCount = $faker->numberBetween(1, 2);
                $selectedFeatures = $features->random($overrideCount);
                
                foreach ($selectedFeatures as $feature) {
                    $value = match ($feature->feature_value_type_id) {
                        \App\Models\FeatureValueType::BOOLEAN => $faker->randomElement(['true', 'false']),
                        \App\Models\FeatureValueType::NUMBER => (string) $faker->numberBetween(10, 100),
                        \App\Models\FeatureValueType::TEXT => $faker->word(),
                        default => 'true',
                    };
                    
                    UserPlanOverride::create([
                        'user_id' => $user->id,
                        'feature_id' => $feature->id,
                        'override_value' => $value,
                        'expires_at' => $faker->optional(0.3)->dateTimeBetween('+1 month', '+1 year'),
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }
}

