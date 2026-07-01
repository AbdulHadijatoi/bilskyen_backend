<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->id();
                $table->string('group', 64)->index();
                $table->string('key', 128);
                $table->longText('value')->nullable();
                $table->boolean('is_encrypted')->default(false);
                $table->timestamps();
                $table->unique(['group', 'key']);
            });
        }

        if (! Schema::hasTable('integration_logs')) {
            Schema::create('integration_logs', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 64)->index();
                $table->string('action', 128);
                $table->string('status', 32)->index();
                $table->text('message')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (Schema::hasTable('dealers')) {
            Schema::table('dealers', function (Blueprint $table) {
                if (! Schema::hasColumn('dealers', 'onboarding_step')) {
                    $table->unsignedTinyInteger('onboarding_step')->default(0)->after('logo_path');
                }
                if (! Schema::hasColumn('dealers', 'onboarding_completed_at')) {
                    $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
                }
            });
        }

        if (Schema::hasTable('saved_searches') && ! Schema::hasColumn('saved_searches', 'name')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->string('name')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dealers')) {
            Schema::table('dealers', function (Blueprint $table) {
                if (Schema::hasColumn('dealers', 'onboarding_completed_at')) {
                    $table->dropColumn('onboarding_completed_at');
                }
                if (Schema::hasColumn('dealers', 'onboarding_step')) {
                    $table->dropColumn('onboarding_step');
                }
            });
        }

        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('platform_settings');

        if (Schema::hasTable('saved_searches') && Schema::hasColumn('saved_searches', 'name')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
