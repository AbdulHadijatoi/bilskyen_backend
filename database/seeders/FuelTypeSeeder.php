<?php

namespace Database\Seeders;

use App\Models\DmrDriveEnergy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $fuelTypes = [
                ['name' => 'Petrol'],
                ['name' => 'Diesel'],
                ['name' => 'Electric'],
                ['name' => 'Hybrid'],
                ['name' => 'Plug-in Hybrid'],
            ];

            foreach ($fuelTypes as $fuelType) {
                DmrDriveEnergy::query()->firstOrCreate(
                    ['name' => $fuelType['name']]
                );
            }
        });
    }
}

