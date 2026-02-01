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
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedInteger('lead_intent_id')->nullable()->after('lead_stage_id');
            $table->index('lead_intent_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('lead_intent_id')->references('id')->on('lead_intents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['lead_intent_id']);
            $table->dropIndex(['lead_intent_id']);
            $table->dropColumn('lead_intent_id');
        });
    }
};
