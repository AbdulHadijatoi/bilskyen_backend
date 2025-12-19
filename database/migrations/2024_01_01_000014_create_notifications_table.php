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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('message', 1000);
            $table->json('target_roles')->comment('Array of role strings: user, dealer, admin');
            $table->boolean('sent')->default(false);
            $table->timestamp('scheduled_at')->useCurrent()->comment('Auto-delete after 30 days');
            $table->json('metadata')->nullable()->comment('NotificationMetadata object');
            $table->timestamps();

            // Indexes
            $table->index('scheduled_at', 'idx_notifications_scheduled_at');
            $table->index('sent', 'idx_notifications_sent');
            $table->index('created_at', 'idx_notifications_created_at');
        });

        // Add CHECK constraints using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            $lengthFunc = DB::getDriverName() === 'mysql' ? 'CHAR_LENGTH' : 'LENGTH';
            DB::statement("ALTER TABLE notifications ADD CONSTRAINT chk_notifications_title_length CHECK ({$lengthFunc}(title) <= 150)");
            DB::statement("ALTER TABLE notifications ADD CONSTRAINT chk_notifications_message_length CHECK ({$lengthFunc}(message) <= 1000)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

