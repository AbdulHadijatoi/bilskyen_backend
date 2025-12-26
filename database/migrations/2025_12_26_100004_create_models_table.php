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
        Schema::dropIfExists('models');
        
        Schema::create('models', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->unsignedInteger('brand_id');
            $table->string('name', 100);
            
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->index('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('models');
    }
};

