<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $sources = [
                ['name' => 'Website'],
                ['name' => 'Phone'],
                ['name' => 'Email'],
                ['name' => 'Referral'],
                ['name' => 'Social Media'],
                ['name' => 'Walk-in'],
            ];

            foreach ($sources as $source) {
                Source::firstOrCreate(['name' => $source['name']]);
            }
        });
    }
}

