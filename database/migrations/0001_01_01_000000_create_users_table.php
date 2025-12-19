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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->boolean('email_verified')->default(false);
            $table->string('phone')->nullable()->comment('Custom field');
            $table->text('address')->nullable()->comment('Custom field');
            $table->string('role', 50)->default('user')->comment('Enum: user, dealer, admin');
            $table->string('image', 500)->nullable()->comment('Profile image URL');
            $table->boolean('banned')->default(false);
            $table->text('ban_reason')->nullable();
            $table->timestamp('ban_expires')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('role');
            $table->index('banned');
            $table->fullText(['name', 'email']);
        });

        // Add CHECK constraint using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN (\'user\', \'dealer\', \'admin\'))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
