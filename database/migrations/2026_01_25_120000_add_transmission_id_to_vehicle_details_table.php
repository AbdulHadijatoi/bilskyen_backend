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
        if (Schema::hasTable('vehicle_details')) {
            Schema::table('vehicle_details', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicle_details', 'transmission_id')) {
                    $table->unsignedInteger('transmission_id')->nullable()->after('body_type_id');
                    $table->index('transmission_id');
                }
            });

            // Add foreign key constraint if transmissions table exists
            if (Schema::hasTable('transmissions')) {
                Schema::table('vehicle_details', function (Blueprint $table) {
                    // Check if foreign key already exists
                    $foreignKeys = DB::select(
                        "SELECT CONSTRAINT_NAME 
                         FROM information_schema.KEY_COLUMN_USAGE 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'vehicle_details' 
                         AND COLUMN_NAME = 'transmission_id' 
                         AND CONSTRAINT_NAME LIKE '%_foreign'
                         AND REFERENCED_TABLE_NAME = 'transmissions'"
                    );
                    
                    if (empty($foreignKeys)) {
                        $table->foreign('transmission_id')
                            ->references('id')
                            ->on('transmissions')
                            ->nullOnDelete();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicle_details')) {
            Schema::table('vehicle_details', function (Blueprint $table) {
                // Drop foreign key first (using column name)
                if (Schema::hasColumn('vehicle_details', 'transmission_id')) {
                    // Check if foreign key exists
                    $foreignKeys = DB::select(
                        "SELECT CONSTRAINT_NAME 
                         FROM information_schema.KEY_COLUMN_USAGE 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'vehicle_details' 
                         AND COLUMN_NAME = 'transmission_id' 
                         AND CONSTRAINT_NAME LIKE '%_foreign'"
                    );
                    
                    if (!empty($foreignKeys)) {
                        $table->dropForeign(['transmission_id']);
                    }
                    
                    // Drop column
                    $table->dropColumn('transmission_id');
                }
            });
        }
    }
};
