<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listing_health_events')) {
            Schema::create('listing_health_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('fix_type', 64);
                $table->string('issue_key', 64)->nullable();
                $table->json('before_metrics')->nullable();
                $table->json('after_metrics')->nullable();
                $table->timestamp('fixed_at');
                $table->timestamp('measured_at')->nullable();
                $table->string('status', 32)->default('pending');
                $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['dealer_id', 'status']);
                $table->index(['vehicle_id', 'fixed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_health_events');
    }
};
