<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\AuditActorType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed STAFF and SELLER actor types if they don't exist
        $types = [
            ['id' => AuditActorType::STAFF, 'name' => 'Staff'],
            ['id' => AuditActorType::SELLER, 'name' => 'Seller'],
        ];

        foreach ($types as $type) {
            DB::table('audit_actor_types')->updateOrInsert(
                ['id' => $type['id']],
                ['name' => $type['name']]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove STAFF and SELLER actor types
        DB::table('audit_actor_types')
            ->whereIn('id', [AuditActorType::STAFF, AuditActorType::SELLER])
            ->delete();
    }
};
