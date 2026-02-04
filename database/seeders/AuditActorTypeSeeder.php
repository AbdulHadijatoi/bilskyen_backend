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
                ['id' => AuditActorType::SELLER, 'name' => 'Seller'],
                ['id' => AuditActorType::DEALER, 'name' => 'Dealer'],
                ['id' => AuditActorType::ADMIN, 'name' => 'Admin'],
                ['id' => AuditActorType::STAFF, 'name' => 'Staff'],
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

