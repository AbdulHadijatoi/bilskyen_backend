<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Legacy `model_years` table seeding removed: years come from DMR `dmr_fact_vehicles.model_aar`.
 */
class ModelYearSeeder extends Seeder
{
    public function run(): void
    {
        // Intentionally empty — use DMR fact import / pipeline for model years.
    }
}
