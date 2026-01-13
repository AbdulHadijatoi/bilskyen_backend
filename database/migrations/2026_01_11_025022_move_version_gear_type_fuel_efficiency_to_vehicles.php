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
        // Step 1: Add columns to vehicles table if they don't exist
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicles', 'version')) {
                    $table->string('version', 100)->nullable()->after('first_registration_date');
                }
                if (!Schema::hasColumn('vehicles', 'gear_type_id')) {
                    $table->unsignedInteger('gear_type_id')->nullable()->after('version');
                }
                if (!Schema::hasColumn('vehicles', 'fuel_efficiency')) {
                    $table->decimal('fuel_efficiency', 8, 2)->nullable()->after('gear_type_id');
                }
            });
        }

        // Step 2: Migrate data from vehicle_details to vehicles
        DB::statement('
            UPDATE vehicles v
            INNER JOIN vehicle_details vd ON v.id = vd.vehicle_id
            SET 
                v.version = vd.version,
                v.gear_type_id = vd.gear_type_id,
                v.fuel_efficiency = vd.fuel_efficiency
        ');

        // Step 3: Add foreign key constraint for gear_type_id on vehicles (if column exists)
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'gear_type_id')) {
            // Check if index already exists
            $indexExists = DB::select(
                "SELECT INDEX_NAME 
                 FROM information_schema.STATISTICS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'vehicles' 
                 AND COLUMN_NAME = 'gear_type_id'"
            );
            
            if (empty($indexExists)) {
                Schema::table('vehicles', function (Blueprint $table) {
                    $table->index('gear_type_id');
                });
            }
            
            // Check if foreign key already exists
            $foreignKeyExists = DB::select(
                "SELECT CONSTRAINT_NAME 
                 FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'vehicles' 
                 AND COLUMN_NAME = 'gear_type_id' 
                 AND CONSTRAINT_NAME LIKE '%_foreign'"
            );
            
            if (empty($foreignKeyExists) && Schema::hasTable('gear_types')) {
                Schema::table('vehicles', function (Blueprint $table) {
                    $table->foreign('gear_type_id')->references('id')->on('gear_types')->nullOnDelete();
                });
            }
        }

        // Step 4: Drop foreign key constraint for gear_type_id from vehicle_details
        if (Schema::hasTable('vehicle_details')) {
            // Drop foreign key if it exists
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME 
                 FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'vehicle_details' 
                 AND COLUMN_NAME = 'gear_type_id' 
                 AND CONSTRAINT_NAME LIKE '%_foreign'"
            );
            
            foreach ($foreignKeys as $foreignKey) {
                try {
                    DB::statement("ALTER TABLE `vehicle_details` DROP FOREIGN KEY `{$foreignKey->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore
                }
            }
            
            // Drop index if it exists
            $indexes = DB::select(
                "SELECT INDEX_NAME 
                 FROM information_schema.STATISTICS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'vehicle_details' 
                 AND COLUMN_NAME = 'gear_type_id'"
            );
            
            foreach ($indexes as $index) {
                if ($index->INDEX_NAME !== 'PRIMARY') {
                    try {
                        DB::statement("ALTER TABLE `vehicle_details` DROP INDEX `{$index->INDEX_NAME}`");
                    } catch (\Exception $e) {
                        // Index might not exist, ignore
                    }
                }
            }
        }

        // Step 5: Remove columns from vehicle_details table
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->dropColumn(['version', 'gear_type_id', 'fuel_efficiency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add columns back to vehicle_details
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->string('version', 100)->nullable()->after('type_name');
            $table->unsignedInteger('gear_type_id')->nullable()->after('version');
            $table->decimal('fuel_efficiency', 8, 2)->nullable()->after('gross_combination_weight');
        });

        // Step 2: Migrate data back from vehicles to vehicle_details
        DB::statement('
            UPDATE vehicle_details vd
            INNER JOIN vehicles v ON vd.vehicle_id = v.id
            SET 
                vd.version = v.version,
                vd.gear_type_id = v.gear_type_id,
                vd.fuel_efficiency = v.fuel_efficiency
        ');

        // Step 3: Add foreign key constraint back to vehicle_details
        Schema::table('vehicle_details', function (Blueprint $table) {
            $table->index('gear_type_id');
            $table->foreign('gear_type_id')->references('id')->on('gear_types')->nullOnDelete();
        });

        // Step 4: Drop foreign key and index from vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME 
                 FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'vehicles' 
                 AND COLUMN_NAME = 'gear_type_id' 
                 AND CONSTRAINT_NAME LIKE '%_foreign'"
            );
            
            foreach ($foreignKeys as $foreignKey) {
                $table->dropForeign([$foreignKey->CONSTRAINT_NAME]);
            }
            
            $indexes = DB::select(
                "SELECT INDEX_NAME 
                 FROM information_schema.STATISTICS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'vehicles' 
                 AND COLUMN_NAME = 'gear_type_id'"
            );
            
            foreach ($indexes as $index) {
                if ($index->INDEX_NAME !== 'PRIMARY') {
                    try {
                        $table->dropIndex([$index->INDEX_NAME]);
                    } catch (\Exception $e) {
                        // Index might not exist
                    }
                }
            }
        });

        // Step 5: Remove columns from vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['version', 'gear_type_id', 'fuel_efficiency']);
        });
    }
};
