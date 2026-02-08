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
        if (!Schema::hasTable('page_images')) {
            Schema::create('page_images', function (Blueprint $table) {
                $table->id();
                $table->string('page_name', 100)->default('home');
                $table->string('section_key', 100);
                $table->string('image_path');
                $table->string('alt_text')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                
                // Unique index on page_name, section_key, and sort_order combination for ordering
                $table->unique(['page_name', 'section_key', 'sort_order']);
                
                // Index for faster queries by page_name and section_key
                $table->index(['page_name', 'section_key']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_images');
    }
};
