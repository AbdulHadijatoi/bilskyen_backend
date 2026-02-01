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
        if (!Schema::hasTable('enquiries')) {
            Schema::create('enquiries', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('serial_no')->nullable();
                $table->string('subject', 200);
                $table->text('message');
                $table->string('type', 50)->default('General');
                $table->string('status', 50)->default('New');
                $table->string('source', 50)->default('Website');
                $table->unsignedBigInteger('contact_id')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->timestamps();

                $table->index('serial_no');
                $table->index('user_id');
                $table->index('vehicle_id');
                $table->index('contact_id');
                $table->index('status');
                $table->index('type');
            });

            // Add foreign key for contact_id only if contacts table exists
            if (Schema::hasTable('contacts')) {
                Schema::table('enquiries', function (Blueprint $table) {
                    $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
                });
            }
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
