<?php

namespace Database\Seeders;

use App\Models\Transmission;
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
                ['name' => 'CVT'],
                ['name' => 'Semi-Automatic'],
            ];

            foreach ($transmissions as $transmission) {
                Transmission::firstOrCreate(['name' => $transmission['name']]);
            }
        });
    }
}

