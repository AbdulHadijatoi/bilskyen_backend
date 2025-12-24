<?php

namespace Database\Seeders;

use App\Models\PageStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $statuses = [
                ['id' => PageStatus::DRAFT, 'name' => 'Draft'],
                ['id' => PageStatus::PUBLISHED, 'name' => 'Published'],
            ];

            foreach ($statuses as $status) {
                PageStatus::updateOrCreate(
                    ['id' => $status['id']],
                    ['name' => $status['name']]
                );
            }
        });
    }
}

