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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('city', 100);
            $table->string('postcode', 10);
            $table->string('region', 100);
            $table->char('country_code', 2)->default('DK');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->index('postcode');
            $table->index('city');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
