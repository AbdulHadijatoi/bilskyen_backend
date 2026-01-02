<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the column if it doesn't exist
        if (!Schema::hasColumn('users', 'status_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('status_id')->nullable()->after('password');
                $table->index('status_id');
            });
        }
        
        // Check if foreign key already exists
        $foreignKeyExists = DB::select(
            "SELECT CONSTRAINT_NAME 
             FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'users' 
             AND CONSTRAINT_NAME = 'users_status_id_foreign' 
             AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        
        // Add the foreign key constraint if it doesn't exist
        if (empty($foreignKeyExists)) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('status_id')
                    ->references('id')
                    ->on('user_statuses')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
        });
    }
};
