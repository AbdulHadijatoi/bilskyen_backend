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
        if (!Schema::hasTable('seo_pages')) {
            Schema::create('seo_pages', function (Blueprint $table) {
                $table->id();
                $table->string('page_type', 50); // home, listing, vehicle, static, blog, city, brand, model
                $table->string('page_key', 255); // slug or unique identifier
                $table->string('title')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('meta_keywords')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('robots', 100)->nullable(); // index, noindex, follow, nofollow
                $table->string('og_title')->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image')->nullable();
                $table->string('twitter_title')->nullable();
                $table->text('twitter_description')->nullable();
                $table->string('twitter_image')->nullable();
                $table->string('schema_type', 100)->nullable(); // Vehicle, Organization, Breadcrumb, etc.
                $table->json('schema_json')->nullable();
                $table->longText('content_html')->nullable();
                $table->json('faq_json')->nullable();
                $table->json('breadcrumbs_json')->nullable();
                $table->timestamps();

                $table->unique(['page_type', 'page_key']);
                $table->index('page_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
