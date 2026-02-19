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
        if (!Schema::hasTable('seo_sitemaps')) {
            Schema::create('seo_sitemaps', function (Blueprint $table) {
                $table->id();
                $table->string('page_type', 50)->nullable();
                $table->string('url', 2048); // full or path
                $table->decimal('priority', 3, 2)->nullable();
                $table->string('changefreq', 20)->nullable(); // always, hourly, daily, weekly, monthly, yearly, never
                $table->dateTime('lastmod')->nullable();
                $table->timestamps();

                $table->index('page_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_sitemaps');
    }
};
