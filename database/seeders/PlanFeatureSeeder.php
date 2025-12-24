<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Feature;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PlanFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $plans = Plan::all();
            $features = Feature::all();
            
            if ($plans->isEmpty() || $features->isEmpty()) {
                return;
            }
            
            // Define plan features mapping
            $planFeatures = [
                'basic' => [
                    'max_listings' => '10',
                    'advanced_analytics' => 'false',
                    'priority_support' => 'false',
                    'custom_branding' => 'false',
                    'api_access' => 'false',
                    'lead_management' => 'true',
                    'storage_limit_gb' => '5',
                ],
                'professional' => [
                    'max_listings' => '50',
                    'advanced_analytics' => 'true',
                    'priority_support' => 'true',
                    'custom_branding' => 'false',
                    'api_access' => 'true',
                    'lead_management' => 'true',
                    'storage_limit_gb' => '50',
                ],
                'enterprise' => [
                    'max_listings' => '999',
                    'advanced_analytics' => 'true',
                    'priority_support' => 'true',
                    'custom_branding' => 'true',
                    'api_access' => 'true',
                    'lead_management' => 'true',
                    'storage_limit_gb' => '500',
                    'custom_domain' => 'example.com',
                ],
                'trial' => [
                    'max_listings' => '3',
                    'advanced_analytics' => 'false',
                    'priority_support' => 'false',
                    'custom_branding' => 'false',
                    'api_access' => 'false',
                    'lead_management' => 'true',
                    'storage_limit_gb' => '1',
                ],
            ];
            
            foreach ($plans as $plan) {
                $planFeatureMap = $planFeatures[$plan->slug] ?? [];
                
                foreach ($features as $feature) {
                    if (isset($planFeatureMap[$feature->key])) {
                        PlanFeature::firstOrCreate(
                            [
                                'plan_id' => $plan->id,
                                'feature_id' => $feature->id,
                            ],
                            [
                                'value' => $planFeatureMap[$feature->key],
                            ]
                        );
                    }
                }
            }
        });
    }
}

