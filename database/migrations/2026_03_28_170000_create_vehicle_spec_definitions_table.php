<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_spec_definitions')) {
            return;
        }

        Schema::create('vehicle_spec_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('model_id')->constrained('models')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
            $table->unsignedSmallInteger('model_year');
            $table->string('name', 255);
            $table->text('value');
            $table->timestamps();

            $table->unique(
                ['brand_id', 'model_id', 'variant_id', 'model_year', 'name'],
                'vehicle_spec_definitions_scope_name_unique'
            );
            $table->index('brand_id');
            $table->index('model_id');
            $table->index('variant_id');
            $table->index('model_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_spec_definitions');
    }
};
