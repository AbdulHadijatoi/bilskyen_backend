<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_specifications')) {
            return;
        }

        Schema::create('vehicle_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('specification_id')->constrained('specifications')->cascadeOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['vehicle_id', 'specification_id']);
            $table->index('vehicle_id');
            $table->index('specification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_specifications');
    }
};
