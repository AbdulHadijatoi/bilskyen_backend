<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Plan features are defined in migration 2026_02_03_131823_seed_plans_table.php
 * and 2026_06_30_100000_add_billing_model_and_usage_tables.php.
 * This seeder is intentionally a no-op to avoid conflicting dev data.
 */
class PlanFeatureSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: use migrations as source of truth for plan features.
    }
}
