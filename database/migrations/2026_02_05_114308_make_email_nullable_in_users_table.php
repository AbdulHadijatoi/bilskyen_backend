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
        // Drop the unique index on email first (if it exists)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        } catch (\Exception $e) {
            // Index might have a different name, try to find and drop it
            // Or it might already be dropped, continue anyway
        }

        // Make email nullable to support staff users without email
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->change();
        });

        // Re-add the unique constraint (allows multiple NULLs in MySQL)
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique index
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Make email required again
            // Note: This will fail if there are any NULL emails in the database
            $table->string('email', 150)->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            // Re-add the unique constraint
            $table->unique('email');
        });
    }
};
