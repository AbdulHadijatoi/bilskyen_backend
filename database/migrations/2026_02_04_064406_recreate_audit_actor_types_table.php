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
     * 
     * Note: This will truncate audit_logs table to handle foreign key constraints.
     * If you need to preserve audit logs, update the references before running this migration.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate audit_logs first (due to foreign key constraint)
        // This is necessary because we're changing the IDs of actor types
        DB::table('audit_logs')->truncate();
        
        // Truncate audit_actor_types table
        DB::table('audit_actor_types')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // Insert actor types in the specified order:
        // 1) Seller
        // 2) Dealer
        // 3) Admin
        // 4) Staff
        // 5) System
        $actorTypes = [
            ['id' => AuditActorType::SELLER, 'name' => 'Seller'],
            ['id' => AuditActorType::DEALER, 'name' => 'Dealer'],
            ['id' => AuditActorType::ADMIN, 'name' => 'Admin'],
            ['id' => AuditActorType::STAFF, 'name' => 'Staff'],
            ['id' => AuditActorType::SYSTEM, 'name' => 'System'],
        ];

        DB::table('audit_actor_types')->insert($actorTypes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate audit_logs first (due to foreign key constraint)
        DB::table('audit_logs')->truncate();
        
        // Truncate audit_actor_types table
        DB::table('audit_actor_types')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
