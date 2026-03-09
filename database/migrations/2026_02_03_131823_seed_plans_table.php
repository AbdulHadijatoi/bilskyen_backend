<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate related tables first (due to foreign key constraints)
        DB::table('plan_features')->truncate();
        DB::table('plan_price_history')->truncate();
        DB::table('plan_availability')->truncate();
        DB::table('dealer_subscriptions')->truncate();
        
        // Truncate plans table
        DB::table('plans')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // Get dealer role ID (using DB query since models might not be available in migrations)
        $dealerRole = DB::table('roles')->where('name', 'dealer')->first();
        if (!$dealerRole) {
            throw new \Exception('Dealer role must exist. Run RolesAndPermissionsSeeder first.');
        }

        // Get all features (they should be seeded by the features migration)
        $features = DB::table('features')->get()->keyBy('key');
        
        if ($features->isEmpty()) {
            throw new \Exception('Features must be seeded first. Run the features migration before this one.');
        }

        // Create Plan 1: Basic Plan
        $basicPlanId = DB::table('plans')->insertGetId([
            'name' => 'Basic Plan',
            'slug' => 'basic',
            'description' => 'Perfect for small dealerships getting started',
            'is_active' => true,
            'trial_days' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add pricing for Basic Plan
        DB::table('plan_price_history')->insert([
            [
                'plan_id' => $basicPlanId,
                'price' => 9900, // 99.00 DKK in cents
                'currency' => 'DKK',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => null,
            ],
            [
                'plan_id' => $basicPlanId,
                'price' => 99000, // 990.00 DKK in cents (save 10%)
                'currency' => 'DKK',
                'billing_cycle' => 'yearly',
                'starts_at' => now(),
                'ends_at' => null,
            ],
        ]);

        // Add features to Basic Plan
        $basicPlanFeatures = [
            'max_listings' => '10',
            'enquiry_management' => 'true',
            'lead_management' => 'true',
            'staff_management' => 'true',
            'max_feature_listings' => '2',
            'priority_support' => 'false',
            'analytics' => 'false',
            'max_vehicle_images' => '10',
            'max_equipment_per_vehicle' => '10',
            'upload_3d_view' => 'false',
        ];

        foreach ($basicPlanFeatures as $featureKey => $value) {
            if (isset($features[$featureKey])) {
                DB::table('plan_features')->insert([
                    'plan_id' => $basicPlanId,
                    'feature_id' => $features[$featureKey]->id,
                    'value' => $value,
                ]);
            }
        }

        // Make Basic Plan available to dealer role
        DB::table('plan_availability')->insert([
            'plan_id' => $basicPlanId,
            'allowed_role_id' => $dealerRole->id,
            'dealer_id' => null,
            'is_enabled' => true,
            'created_at' => now(),
        ]);

        // Create Plan 2: Professional Plan
        $professionalPlanId = DB::table('plans')->insertGetId([
            'name' => 'Professional Plan',
            'slug' => 'professional',
            'description' => 'Ideal for growing dealerships with advanced needs',
            'is_active' => true,
            'trial_days' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add pricing for Professional Plan
        DB::table('plan_price_history')->insert([
            [
                'plan_id' => $professionalPlanId,
                'price' => 19900, // 199.00 DKK in cents
                'currency' => 'DKK',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => null,
            ],
            [
                'plan_id' => $professionalPlanId,
                'price' => 199000, // 1990.00 DKK in cents (save 10%)
                'currency' => 'DKK',
                'billing_cycle' => 'yearly',
                'starts_at' => now(),
                'ends_at' => null,
            ],
        ]);

        // Add features to Professional Plan
        $professionalPlanFeatures = [
            'max_listings' => '50',
            'enquiry_management' => 'true',
            'lead_management' => 'true',
            'staff_management' => 'true',
            'max_feature_listings' => '10',
            'priority_support' => 'true',
            'analytics' => 'true',
            'max_vehicle_images' => '20',
            'max_equipment_per_vehicle' => '30',
            'upload_3d_view' => 'true',
        ];

        foreach ($professionalPlanFeatures as $featureKey => $value) {
            if (isset($features[$featureKey])) {
                DB::table('plan_features')->insert([
                    'plan_id' => $professionalPlanId,
                    'feature_id' => $features[$featureKey]->id,
                    'value' => $value,
                ]);
            }
        }

        // Make Professional Plan available to dealer role
        DB::table('plan_availability')->insert([
            'plan_id' => $professionalPlanId,
            'allowed_role_id' => $dealerRole->id,
            'dealer_id' => null,
            'is_enabled' => true,
            'created_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('plan_features')->truncate();
        DB::table('plan_price_history')->truncate();
        DB::table('plan_availability')->truncate();
        DB::table('dealer_subscriptions')->truncate();
        DB::table('plans')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
