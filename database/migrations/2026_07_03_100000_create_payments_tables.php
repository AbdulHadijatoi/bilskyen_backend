<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dealers') && ! Schema::hasColumn('dealers', 'stripe_customer_id')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->string('stripe_customer_id', 128)->nullable()->after('onboarding_completed_at');
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('provider', 32)->default('stripe');
                $table->string('purpose', 64);
                $table->nullableMorphs('payable');
                $table->unsignedInteger('amount_cents');
                $table->string('currency', 3)->default('DKK');
                $table->string('status', 32)->default('pending');
                $table->string('stripe_checkout_session_id')->nullable()->index();
                $table->string('stripe_payment_intent_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_webhook_events')) {
            Schema::create('payment_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 32);
                $table->string('event_id')->unique();
                $table->string('type', 128);
                $table->string('status', 32)->default('received');
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');

        if (Schema::hasTable('dealers') && Schema::hasColumn('dealers', 'stripe_customer_id')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->dropColumn('stripe_customer_id');
            });
        }
    }
};
