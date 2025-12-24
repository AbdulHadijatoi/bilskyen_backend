<?php

namespace Database\Seeders;

use App\Models\FeatureValueType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureValueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $types = [
                ['id' => FeatureValueType::BOOLEAN, 'name' => 'Boolean'],
                ['id' => FeatureValueType::NUMBER, 'name' => 'Number'],
                ['id' => FeatureValueType::TEXT, 'name' => 'Text'],
            ];

            foreach ($types as $type) {
                FeatureValueType::updateOrCreate(
                    ['id' => $type['id']],
                    ['name' => $type['name']]
                );
            }
        });
    }
}

