<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Dev/local seeder: aligns plan data with production migration seeds.
 * Run migrations first; this seeder only ensures vehicle statuses exist.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(VehicleListStatusSeeder::class);
    }
}
