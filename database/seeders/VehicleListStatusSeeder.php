<?php

namespace Database\Seeders;

use App\Models\VehicleListStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleListStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $statuses = [
                ['id' => VehicleListStatus::DRAFT, 'name' => 'Draft'],
                ['id' => VehicleListStatus::PUBLISHED, 'name' => 'Published'],
                ['id' => VehicleListStatus::SOLD, 'name' => 'Sold'],
                ['id' => VehicleListStatus::ARCHIVED, 'name' => 'Archived'],
            ];

            foreach ($statuses as $status) {
                VehicleListStatus::updateOrCreate(
                    ['id' => $status['id']],
                    ['name' => $status['name']]
                );
            }
        });
    }
}

