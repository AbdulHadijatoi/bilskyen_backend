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
        Schema::table('equipments', function (Blueprint $table) {
            $table->unsignedInteger('equipment_type_id')->nullable()->after('name');
            $table->foreign('equipment_type_id')
                  ->references('id')
                  ->on('equipment_types')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropForeign(['equipment_type_id']);
            $table->dropColumn('equipment_type_id');
        });
    }
};
