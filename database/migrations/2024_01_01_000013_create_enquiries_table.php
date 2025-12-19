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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number');
            $table->string('subject', 200)->comment('3-200 chars');
            $table->text('message')->comment('Max 5000 chars');
            $table->string('type', 50)->comment('Enum: ENQUIRY_TYPES');
            $table->string('status', 50)->default('New')->comment('Enum: ENQUIRY_STATUSES');
            $table->string('source', 50)->comment('Enum: ENQUIRY_SOURCES');
            $table->foreignId('contact_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_enquiries_created_at');
            $table->index('status', 'idx_enquiries_status');
            $table->index('type', 'idx_enquiries_type');
            $table->index('source', 'idx_enquiries_source');
            $table->index('contact_id', 'idx_enquiries_contact_id');
            $table->index('user_id', 'idx_enquiries_user_id');
            $table->index('vehicle_id', 'idx_enquiries_vehicle_id');
            $table->index(['status', 'created_at'], 'idx_enquiries_status_created_at');
            $table->fullText(['subject', 'message'], 'idx_enquiries_search');
        });

        // Add CHECK constraint using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            $lengthFunc = DB::getDriverName() === 'mysql' ? 'CHAR_LENGTH' : 'LENGTH';
            DB::statement("ALTER TABLE enquiries ADD CONSTRAINT chk_enquiries_subject_length CHECK ({$lengthFunc}(subject) >= 3 AND {$lengthFunc}(subject) <= 200)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};

