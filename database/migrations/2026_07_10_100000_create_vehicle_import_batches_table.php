<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_import_batches')) {
            return;
        }

        Schema::create('vehicle_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename', 255);
            $table->string('file_path', 500);
            $table->string('file_extension', 10);
            $table->boolean('dry_run')->default(false);
            $table->string('status', 32)->default('pending')->index();
            $table->json('summary')->nullable();
            $table->json('rows')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['dealer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_import_batches');
    }
};
