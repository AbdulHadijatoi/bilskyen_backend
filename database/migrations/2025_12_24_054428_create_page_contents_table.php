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
        if (!Schema::hasTable('page_contents')) {
            Schema::create('page_contents', function (Blueprint $table) {
                $table->id();
                $table->string('page_name', 100)->default('home');
                $table->string('section_key', 100);
                $table->text('content')->nullable();
                $table->timestamps();
                
                // Unique index on page_name and section_key combination
                $table->unique(['page_name', 'section_key']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
