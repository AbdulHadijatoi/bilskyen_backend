<?php

namespace Database\Seeders;

use App\Models\FuelType;
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
                FuelType::firstOrCreate(['name' => $fuelType['name']]);
            }
        });
    }
}

