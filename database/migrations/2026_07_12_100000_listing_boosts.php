<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listing_boosts')) {
            Schema::create('listing_boosts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('source', 32)->default('manual');
                $table->timestamp('started_at');
                $table->timestamp('expires_at');
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['dealer_id', 'expires_at']);
                $table->index(['vehicle_id', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_boosts');
    }
};
