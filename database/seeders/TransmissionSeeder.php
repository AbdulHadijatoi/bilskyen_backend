<?php

namespace Database\Seeders;

use App\Models\GearType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $transmissions = [
                ['name' => 'Manual'],
                ['name' => 'Automatic'],
            ];

            foreach ($transmissions as $transmission) {
                GearType::firstOrCreateInsensitive(['name' => $transmission['name']]);
            }
        });
    }
}

