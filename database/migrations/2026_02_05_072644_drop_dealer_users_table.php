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
        Schema::dropIfExists('dealer_users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration should only be run after data migration is complete
        // Recreating the table structure would require the original schema
        // For safety, we don't recreate it in down()
    }
};
