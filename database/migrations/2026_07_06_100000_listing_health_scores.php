<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listing_health_scores')) {
            Schema::create('listing_health_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->unsignedTinyInteger('score')->default(0);
                $table->string('grade', 32)->default('needs_attention');
                $table->unsignedInteger('priority_score')->default(0);
                $table->json('issues')->nullable();
                $table->json('metrics')->nullable();
                $table->json('pricing')->nullable();
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();
                $table->unique('vehicle_id');
                $table->index(['dealer_id', 'score']);
                $table->index(['dealer_id', 'priority_score']);
            });
        }

        if (! Schema::hasTable('listing_health_daily_dealer')) {
            Schema::create('listing_health_daily_dealer', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->date('date')->index();
                $table->unsignedTinyInteger('avg_score')->default(0);
                $table->unsignedTinyInteger('platform_avg_score')->default(0);
                $table->unsignedSmallInteger('attention_count')->default(0);
                $table->unsignedSmallInteger('published_count')->default(0);
                $table->timestamps();
                $table->unique(['dealer_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_health_daily_dealer');
        Schema::dropIfExists('listing_health_scores');
    }
};
