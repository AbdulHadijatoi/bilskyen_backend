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
        Schema::table('plan_availability', function (Blueprint $table) {
            // Add dealer_id column (nullable) for specific dealer availability
            $table->foreignId('dealer_id')->nullable()->after('allowed_role_id')->constrained('dealers')->nullOnDelete();
            
            // Note: Database-level CHECK constraint for "at least one must be set" 
            // is not supported by all databases in Laravel migrations.
            // We'll enforce this validation at the application level (model/controller).
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_availability', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
            $table->dropColumn('dealer_id');
        });
    }
};
