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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number');
            $table->date('sale_date');
            $table->string('sale_type', 50)->comment('Enum: SALE_TYPES');
            $table->string('payment_mode', 50)->comment('Enum: PAYMENT_MODES');
            $table->json('images')->comment('Array of file URLs, max 20 items');
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('received_to_financial_account_id')->constrained('financial_accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_sales_created_at');
            $table->index('sale_date', 'idx_sales_sale_date');
            $table->index('vehicle_id', 'idx_sales_vehicle_id');
            $table->index('contact_id', 'idx_sales_contact_id');
            $table->index('transaction_id', 'idx_sales_transaction_id');
            $table->index('received_to_financial_account_id', 'idx_sales_received_to_financial_account_id');
            $table->index(['sale_date', 'created_at'], 'idx_sales_sale_date_created_at');
            $table->index(['vehicle_id', 'sale_date'], 'idx_sales_vehicle_id_sale_date');
            $table->index(['contact_id', 'sale_date'], 'idx_sales_contact_id_sale_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

