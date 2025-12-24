<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\Feature;
use App\Models\DealerPlanOverride;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class DealerPlanOverrideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $dealers = Dealer::take(3)->get();
            $features = Feature::all();
            
            if ($dealers->isEmpty() || $features->isEmpty()) {
                return;
            }
            
            // Create overrides for a few dealers
            foreach ($dealers as $dealer) {
                // Give 1-3 feature overrides per dealer
                $overrideCount = $faker->numberBetween(1, 3);
                $selectedFeatures = $features->random($overrideCount);
                
                foreach ($selectedFeatures as $feature) {
                    $value = match ($feature->feature_value_type_id) {
                        \App\Models\FeatureValueType::BOOLEAN => $faker->randomElement(['true', 'false']),
                        \App\Models\FeatureValueType::NUMBER => (string) $faker->numberBetween(50, 200),
                        \App\Models\FeatureValueType::TEXT => $faker->domainName(),
                        default => 'true',
                    };
                    
                    DealerPlanOverride::create([
                        'dealer_id' => $dealer->id,
                        'feature_id' => $feature->id,
                        'override_value' => $value,
                        'expires_at' => $faker->optional(0.2)->dateTimeBetween('+6 months', '+2 years'),
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }
}

