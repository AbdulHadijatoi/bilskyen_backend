<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $plans = [
                [
                    'name' => 'Basic',
                    'slug' => 'basic',
                    'description' => 'Perfect for small dealerships getting started',
                    'is_active' => true,
                ],
                [
                    'name' => 'Professional',
                    'slug' => 'professional',
                    'description' => 'For growing dealerships with advanced needs',
                    'is_active' => true,
                ],
                [
                    'name' => 'Enterprise',
                    'slug' => 'enterprise',
                    'description' => 'Full-featured plan for large dealerships',
                    'is_active' => true,
                ],
                [
                    'name' => 'Trial',
                    'slug' => 'trial',
                    'description' => 'Free trial plan for new users',
                    'is_active' => true,
                ],
            ];
            
            foreach ($plans as $planData) {
                Plan::firstOrCreate(
                    ['slug' => $planData['slug']],
                    $planData
                );
            }
        });
    }
}

