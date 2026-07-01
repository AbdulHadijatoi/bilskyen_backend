<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional display names for subscription features (admin-managed translations).
     * Falls back to title-cased key when null.
     */
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            if (!Schema::hasColumn('features', 'label_en')) {
                $table->string('label_en', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('features', 'label_da')) {
                $table->string('label_da', 255)->nullable()->after('label_en');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            if (Schema::hasColumn('features', 'label_da')) {
                $table->dropColumn('label_da');
            }
            if (Schema::hasColumn('features', 'label_en')) {
                $table->dropColumn('label_en');
            }
        });
    }
};
