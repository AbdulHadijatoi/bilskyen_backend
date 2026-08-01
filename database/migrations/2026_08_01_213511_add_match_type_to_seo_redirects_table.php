<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_redirects')) {
            return;
        }

        Schema::table('seo_redirects', function (Blueprint $table) {
            if (! Schema::hasColumn('seo_redirects', 'match_type')) {
                $table->string('match_type', 16)->default('exact')->after('to_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_redirects')) {
            return;
        }

        Schema::table('seo_redirects', function (Blueprint $table) {
            if (Schema::hasColumn('seo_redirects', 'match_type')) {
                $table->dropColumn('match_type');
            }
        });
    }
};
