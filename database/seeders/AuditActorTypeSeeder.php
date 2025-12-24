<?php

namespace Database\Seeders;

use App\Models\AuditActorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditActorTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $types = [
                ['id' => AuditActorType::ADMIN, 'name' => 'Admin'],
                ['id' => AuditActorType::DEALER, 'name' => 'Dealer'],
                ['id' => AuditActorType::SYSTEM, 'name' => 'System'],
            ];

            foreach ($types as $type) {
                AuditActorType::updateOrCreate(
                    ['id' => $type['id']],
                    ['name' => $type['name']]
                );
            }
        });
    }
}

