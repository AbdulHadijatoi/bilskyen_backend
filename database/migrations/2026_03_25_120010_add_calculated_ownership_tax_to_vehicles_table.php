<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'calculated_ownership_tax')) {
                $table->unsignedInteger('calculated_ownership_tax')->nullable()->after('price');
                $table->index('calculated_ownership_tax');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'calculated_ownership_tax')) {
                $table->dropIndex(['calculated_ownership_tax']);
                $table->dropColumn('calculated_ownership_tax');
            }
        });
    }
};

