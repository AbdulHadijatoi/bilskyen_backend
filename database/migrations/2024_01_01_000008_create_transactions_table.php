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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number');
            $table->string('type', 50)->comment('Enum: TRANSACTION_TYPES');
            $table->date('date');
            $table->string('narration', 250)->comment('3-250 chars');
            $table->string('remarks', 500)->nullable();
            $table->json('images')->comment('Array of file URLs, max 20 items');
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_transactions_created_at');
            $table->index('date', 'idx_transactions_date');
            $table->index(['date', 'created_at'], 'idx_transactions_date_created_at');
        });

        // Add CHECK constraint using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            $lengthFunc = DB::getDriverName() === 'mysql' ? 'CHAR_LENGTH' : 'LENGTH';
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_narration_length CHECK ({$lengthFunc}(narration) >= 3 AND {$lengthFunc}(narration) <= 250)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

