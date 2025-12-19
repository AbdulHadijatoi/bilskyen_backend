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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('account_id', 255)->comment('Provider account ID');
            $table->string('provider_id', 50)->comment('e.g., email, google, github');
            $table->text('access_token')->nullable()->comment('OAuth access token');
            $table->text('refresh_token')->nullable()->comment('OAuth refresh token');
            $table->text('id_token')->nullable()->comment('OAuth ID token');
            $table->timestamp('expires_at')->nullable()->comment('Token expiration');
            $table->string('password', 255)->nullable()->comment('Hashed password (for email provider)');
            $table->timestamps();

            $table->unique(['provider_id', 'account_id'], 'uk_accounts_provider_account');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

