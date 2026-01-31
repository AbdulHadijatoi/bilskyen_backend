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
            $table->unsignedBigInteger('lead_category_id')->nullable()->after('source_id');
            $table->foreign('lead_category_id')->references('id')->on('lead_categories')->nullOnDelete();
            $table->index('lead_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['lead_category_id']);
            $table->dropIndex(['lead_category_id']);
            $table->dropColumn('lead_category_id');
        });
    }
};
