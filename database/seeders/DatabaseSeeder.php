<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First, seed roles and permissions (required for DealerUser and PlanAvailability)
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // 1. Lookup Tables (no dependencies)
        $this->call([
            UserStatusSeeder::class,
            FuelTypeSeeder::class,
            TransmissionSeeder::class,
            VehicleListStatusSeeder::class,
            LeadStageSeeder::class,
            SourceSeeder::class,
            SubscriptionStatusSeeder::class,
            PageStatusSeeder::class,
            FeatureValueTypeSeeder::class,
            AuditActorTypeSeeder::class,
        ]);

        // 2. Core Business Tables
        $this->call([
            LocationSeeder::class,
            DealerSeeder::class,
            UserSeeder::class,
            DealerUserSeeder::class,
        ]);

        // 3. Vehicle Tables
        $this->call([
            VehicleSeeder::class,
            VehicleImageSeeder::class,
        ]);

        // 4. User Features
        $this->call([
            FavoriteSeeder::class,
            SavedSearchSeeder::class,
        ]);

        // 5. Lead Management
        $this->call([
            LeadSeeder::class,
            LeadStageHistorySeeder::class,
            ChatThreadSeeder::class,
            ChatMessageSeeder::class,
        ]);

        // 6. CMS
        $this->call([
            PageSeeder::class,
            BlogSeeder::class,
        ]);

        // 7. Subscriptions
        $this->call([
            FeatureSeeder::class,
            PlanSeeder::class,
            PlanFeatureSeeder::class,
            PlanPriceHistorySeeder::class,
            PlanAvailabilitySeeder::class,
            DealerSubscriptionSeeder::class,
            UserPlanOverrideSeeder::class,
            DealerPlanOverrideSeeder::class,
        ]);

        // 8. Analytics & Logging
        $this->call([
            PriceHistorySeeder::class,
            ListingViewsLogSeeder::class,
            AuditLogSeeder::class,
            ApiLogSeeder::class,
        ]);
    }
}
