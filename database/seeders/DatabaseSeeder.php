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
        // Note: Roles and permissions are now created via migration (create_permissions_from_routes)
        // Run migrations before seeding to ensure roles and permissions exist

        // 1. Lookup Tables (no dependencies)
        $this->call([
            UserStatusSeeder::class,
            FuelTypeSeeder::class,
            TransmissionSeeder::class,
            ModelYearSeeder::class,
            EquipmentSeeder::class,
            BrandAndModelSeeder::class,
            VehicleListStatusSeeder::class,
            LeadStageSeeder::class,
            SourceSeeder::class,
            SubscriptionStatusSeeder::class,
            PageStatusSeeder::class,
            FeatureValueTypeSeeder::class,
            AuditActorTypeSeeder::class,
            LocationSeeder::class,
        ]);

        // 2. Core Business Tables
        $this->call([
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
            CmsTemplateDemoSeeder::class,
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
