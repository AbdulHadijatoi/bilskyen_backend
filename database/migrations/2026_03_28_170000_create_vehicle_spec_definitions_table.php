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
            // Match brands/models/variants PKs: integerIncrements() → UNSIGNED INT (not foreignId() BIGINT).
            $table->unsignedInteger('brand_id');
            $table->unsignedInteger('model_id');
            $table->unsignedInteger('variant_id');
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
            $table->foreign('model_id')->references('id')->on('models')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('variants')->cascadeOnDelete();
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
