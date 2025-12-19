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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number');
            $table->date('purchase_date');
            $table->string('purchase_type', 50)->comment('Enum: PURCHASE_TYPES');
            $table->string('payment_mode', 50)->comment('Enum: PAYMENT_MODES');
            $table->json('images')->comment('Array of file URLs, max 20 items');
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('paid_from_financial_account_id')->constrained('financial_accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_purchases_created_at');
            $table->index('purchase_date', 'idx_purchases_purchase_date');
            $table->index('vehicle_id', 'idx_purchases_vehicle_id');
            $table->index('contact_id', 'idx_purchases_contact_id');
            $table->index('transaction_id', 'idx_purchases_transaction_id');
            $table->index('paid_from_financial_account_id', 'idx_purchases_paid_from_financial_account_id');
            $table->index(['purchase_date', 'created_at'], 'idx_purchases_purchase_date_created_at');
            $table->index(['vehicle_id', 'purchase_date'], 'idx_purchases_vehicle_id_purchase_date');
            $table->index(['contact_id', 'purchase_date'], 'idx_purchases_contact_id_purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

