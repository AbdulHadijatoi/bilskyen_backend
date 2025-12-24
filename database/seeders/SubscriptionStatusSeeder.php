<?php

namespace Database\Seeders;

use App\Models\SubscriptionStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $statuses = [
                ['id' => SubscriptionStatus::TRIAL, 'name' => 'Trial'],
                ['id' => SubscriptionStatus::ACTIVE, 'name' => 'Active'],
                ['id' => SubscriptionStatus::EXPIRED, 'name' => 'Expired'],
                ['id' => SubscriptionStatus::CANCELED, 'name' => 'Canceled'],
                ['id' => SubscriptionStatus::SCHEDULED, 'name' => 'Scheduled'],
            ];

            foreach ($statuses as $status) {
                SubscriptionStatus::updateOrCreate(
                    ['id' => $status['id']],
                    ['name' => $status['name']]
                );
            }
        });
    }
}

