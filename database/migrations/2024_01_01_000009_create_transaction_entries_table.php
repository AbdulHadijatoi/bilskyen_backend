<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('financial_account_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('amount', 15, 2)->comment('0 to 999,999,999');
            $table->enum('type', ['debit', 'credit']);
            $table->string('description', 500)->nullable()->comment('Optional entry description');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('transaction_id', 'idx_transaction_entries_transaction_id');
            $table->index('financial_account_id', 'idx_transaction_entries_financial_account_id');
            $table->index('type', 'idx_transaction_entries_type');
        });

        // Add CHECK constraint using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE transaction_entries ADD CONSTRAINT chk_transaction_entries_amount CHECK (amount >= 0 AND amount <= 999999999)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_entries');
    }
};

