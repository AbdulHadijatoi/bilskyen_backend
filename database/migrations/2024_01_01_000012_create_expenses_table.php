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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number');
            $table->date('date');
            $table->string('narration', 250)->comment('3-250 chars');
            $table->string('category', 50)->comment('Enum: EXPENSES_ACTIVITIES');
            $table->string('payment_mode', 50)->comment('Enum: PAYMENT_MODES');
            $table->decimal('paid_amount', 15, 2)->comment('0 to 999,999,999');
            $table->decimal('total_amount', 15, 2)->comment('0 to 999,999,999');
            $table->foreignId('paid_from_financial_account_id')->constrained('financial_accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('remarks', 500)->nullable();
            $table->json('images')->comment('Array of file URLs, max 20 items');
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_expenses_created_at');
            $table->index('date', 'idx_expenses_date');
            $table->index('transaction_id', 'idx_expenses_transaction_id');
            $table->index('contact_id', 'idx_expenses_contact_id');
            $table->index('vehicle_id', 'idx_expenses_vehicle_id');
            $table->index('paid_from_financial_account_id', 'idx_expenses_paid_from_financial_account_id');
            $table->index('category', 'idx_expenses_category');
            $table->index(['date', 'created_at'], 'idx_expenses_date_created_at');
        });

        // Add CHECK constraints using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            $lengthFunc = DB::getDriverName() === 'mysql' ? 'CHAR_LENGTH' : 'LENGTH';
            DB::statement('ALTER TABLE expenses ADD CONSTRAINT chk_expenses_paid_amount CHECK (paid_amount >= 0 AND paid_amount <= 999999999)');
            DB::statement('ALTER TABLE expenses ADD CONSTRAINT chk_expenses_total_amount CHECK (total_amount >= 0 AND total_amount <= 999999999)');
            DB::statement("ALTER TABLE expenses ADD CONSTRAINT chk_expenses_narration_length CHECK ({$lengthFunc}(narration) >= 3 AND {$lengthFunc}(narration) <= 250)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

