<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('euronorms') && !Schema::hasTable('euronorm')) {
            Schema::rename('euronorms', 'euronorm');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('euronorm') && !Schema::hasTable('euronorms')) {
            Schema::rename('euronorm', 'euronorms');
        }
    }
};

