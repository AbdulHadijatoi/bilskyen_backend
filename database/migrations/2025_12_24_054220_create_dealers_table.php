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
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('cvr', 20)->unique()->comment('Danish CVR');
            $table->text('address');
            $table->string('city', 100);
            $table->string('postcode', 10);
            $table->char('country_code', 2)->default('DK');
            $table->string('logo_path', 255)->nullable();
            $table->timestamps();

            $table->index('cvr');
            $table->index('postcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
