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
        Schema::create('push_notification_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 500)->unique()->comment('Web Push subscription endpoint URL');
            $table->string('p256dh', 255)->comment('Web Push public key');
            $table->string('auth', 255)->comment('Web Push auth secret');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('device_id', 255)->nullable()->comment('Device identifier');
            $table->timestamp('expiration_time')->nullable()->comment('Subscription expiration');
            $table->boolean('is_active')->default(true)->comment('Soft delete flag');
            $table->timestamps();

            $table->unique(['user_id', 'device_id'], 'uk_push_subscriptions_user_device');
            $table->index('endpoint', 'idx_push_subscriptions_endpoint');
            $table->index('user_id', 'idx_push_subscriptions_user_id');
            $table->index('is_active', 'idx_push_subscriptions_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_subscriptions');
    }
};

