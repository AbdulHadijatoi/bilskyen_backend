<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Plan pricing is defined in migration 2026_02_03_131823_seed_plans_table.php.
 * Pay-as-you-go plans use price_per_listing_per_day on the plans table.
 * This seeder is intentionally a no-op to avoid conflicting dev data.
 */
class PlanPriceHistorySeeder extends Seeder
{
    public function run(): void
    {
        // No-op: use migrations as source of truth for subscription plan pricing.
    }
}
