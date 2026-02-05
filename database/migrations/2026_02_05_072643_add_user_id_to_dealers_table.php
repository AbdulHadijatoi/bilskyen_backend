<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('dealers', 'user_id')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->nullable()->constrained('users')->nullOnDelete();
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('dealers', 'user_id')) {
            Schema::table('dealers', function (Blueprint $table) {
                // Try to drop foreign key if it exists
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore
                }
                
                // Try to drop index if it exists
                try {
                    $table->dropIndex(['user_id']);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
                
                $table->dropColumn('user_id');
            });
        }
    }
};
