<?php

namespace Database\Seeders;

use App\Models\ModelYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds model years commonly used for Danish vehicle listings (1970–2026).
     */
    public function run(): void
    {
        DB::transaction(function () {
            $startYear = 1970;
            $endYear = 2026; // Danish vehicle model years through 2026

            for ($year = $startYear; $year <= $endYear; $year++) {
                ModelYear::firstOrCreateInsensitive(['name' => (string) $year]);
            }
        });
    }
}
