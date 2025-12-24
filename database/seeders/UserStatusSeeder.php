<?php

namespace Database\Seeders;

use App\Models\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $statuses = [
                ['id' => UserStatus::ACTIVE, 'name' => 'Active'],
                ['id' => UserStatus::INACTIVE, 'name' => 'Inactive'],
                ['id' => UserStatus::SUSPENDED, 'name' => 'Suspended'],
            ];

            foreach ($statuses as $status) {
                UserStatus::updateOrCreate(
                    ['id' => $status['id']],
                    ['name' => $status['name']]
                );
            }
        });
    }
}

