<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('analytics_daily_dealer')) {
            Schema::create('analytics_daily_dealer', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->date('date')->index();
                $table->unsignedInteger('views_count')->default(0);
                $table->unsignedInteger('enquiries_count')->default(0);
                $table->unsignedInteger('leads_count')->default(0);
                $table->unsignedInteger('leads_won_count')->default(0);
                $table->unsignedInteger('vehicles_published')->default(0);
                $table->unsignedInteger('vehicles_sold')->default(0);
                $table->unsignedBigInteger('payg_cents')->default(0);
                $table->unsignedBigInteger('payment_cents')->default(0);
                $table->timestamps();
                $table->unique(['dealer_id', 'date']);
            });
        }

        if (! Schema::hasTable('analytics_daily_platform')) {
            Schema::create('analytics_daily_platform', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->unsignedInteger('views_count')->default(0);
                $table->unsignedInteger('enquiries_count')->default(0);
                $table->unsignedInteger('leads_count')->default(0);
                $table->unsignedInteger('leads_won_count')->default(0);
                $table->unsignedInteger('vehicles_published')->default(0);
                $table->unsignedInteger('vehicles_sold')->default(0);
                $table->unsignedInteger('active_dealers')->default(0);
                $table->unsignedInteger('new_dealers')->default(0);
                $table->unsignedInteger('payments_succeeded')->default(0);
                $table->unsignedInteger('payments_failed')->default(0);
                $table->unsignedBigInteger('payment_volume_cents')->default(0);
                $table->unsignedInteger('ai_requests')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_dealer');
        Schema::dropIfExists('analytics_daily_platform');
    }
};
