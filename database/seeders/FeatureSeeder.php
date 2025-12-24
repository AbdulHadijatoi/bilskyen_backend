<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\FeatureValueType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $booleanType = FeatureValueType::find(FeatureValueType::BOOLEAN);
            $numberType = FeatureValueType::find(FeatureValueType::NUMBER);
            $textType = FeatureValueType::find(FeatureValueType::TEXT);
            
            $features = [
                [
                    'key' => 'max_listings',
                    'feature_value_type_id' => $numberType->id,
                    'description' => 'Maximum number of vehicle listings',
                ],
                [
                    'key' => 'advanced_analytics',
                    'feature_value_type_id' => $booleanType->id,
                    'description' => 'Access to advanced analytics dashboard',
                ],
                [
                    'key' => 'priority_support',
                    'feature_value_type_id' => $booleanType->id,
                    'description' => 'Priority customer support',
                ],
                [
                    'key' => 'custom_branding',
                    'feature_value_type_id' => $booleanType->id,
                    'description' => 'Custom branding options',
                ],
                [
                    'key' => 'api_access',
                    'feature_value_type_id' => $booleanType->id,
                    'description' => 'API access for integrations',
                ],
                [
                    'key' => 'lead_management',
                    'feature_value_type_id' => $booleanType->id,
                    'description' => 'Advanced lead management features',
                ],
                [
                    'key' => 'storage_limit_gb',
                    'feature_value_type_id' => $numberType->id,
                    'description' => 'Storage limit in GB',
                ],
                [
                    'key' => 'custom_domain',
                    'feature_value_type_id' => $textType->id,
                    'description' => 'Custom domain name',
                ],
            ];
            
            foreach ($features as $featureData) {
                Feature::firstOrCreate(
                    ['key' => $featureData['key']],
                    array_merge($featureData, ['created_at' => now()])
                );
            }
        });
    }
}

