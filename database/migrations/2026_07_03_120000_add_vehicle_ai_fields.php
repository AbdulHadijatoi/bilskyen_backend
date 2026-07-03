<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'highlights')) {
                $table->text('highlights')->nullable()->after('description');
            }
            if (! Schema::hasColumn('vehicles', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('highlights');
            }
            if (! Schema::hasColumn('vehicles', 'meta_description')) {
                $table->string('meta_description', 500)->nullable()->after('meta_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
            if (Schema::hasColumn('vehicles', 'meta_title')) {
                $table->dropColumn('meta_title');
            }
            if (Schema::hasColumn('vehicles', 'highlights')) {
                $table->dropColumn('highlights');
            }
        });
    }
};
