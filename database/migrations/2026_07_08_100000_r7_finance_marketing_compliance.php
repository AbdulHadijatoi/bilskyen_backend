<?php

use App\Services\PlatformSettingService;
use App\Services\RolePermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dealers')) {
            Schema::table('dealers', function (Blueprint $table) {
                if (! Schema::hasColumn('dealers', 'finance_partner_url')) {
                    $table->string('finance_partner_url', 500)->nullable()->after('logo_path');
                }
                if (! Schema::hasColumn('dealers', 'google_review_url')) {
                    $table->string('google_review_url', 500)->nullable()->after('finance_partner_url');
                }
                if (! Schema::hasColumn('dealers', 'google_place_id')) {
                    $table->string('google_place_id', 128)->nullable()->after('google_review_url');
                }
                if (! Schema::hasColumn('dealers', 'theme_primary_color')) {
                    $table->string('theme_primary_color', 16)->nullable()->after('google_place_id');
                }
                if (! Schema::hasColumn('dealers', 'theme_secondary_color')) {
                    $table->string('theme_secondary_color', 16)->nullable()->after('theme_primary_color');
                }
            });
        }

        if (! Schema::hasTable('trade_in_requests')) {
            Schema::create('trade_in_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
                $table->string('licence_plate', 32)->nullable();
                $table->unsignedInteger('kilometers')->nullable();
                $table->string('expected_price', 64)->nullable();
                $table->text('condition_notes')->nullable();
                $table->string('appraisal_status', 32)->default('pending')->index();
                $table->unsignedBigInteger('offered_value_cents')->nullable();
                $table->text('appraisal_notes')->nullable();
                $table->timestamp('appraised_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dealer_domains')) {
            Schema::create('dealer_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('domain')->unique();
                $table->string('verification_token', 64);
                $table->timestamp('verified_at')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('marketing_consent_logs')) {
            Schema::create('marketing_consent_logs', function (Blueprint $table) {
                $table->id();
                $table->string('email', 150)->index();
                $table->foreignId('dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
                $table->string('consent_type', 64);
                $table->boolean('granted');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('source', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('marketing_unsubscribes')) {
            Schema::create('marketing_unsubscribes', function (Blueprint $table) {
                $table->id();
                $table->string('email', 150);
                $table->foreignId('dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
                $table->timestamp('unsubscribed_at')->useCurrent();
                $table->unique(['email', 'dealer_id']);
            });
        }

        if (! Schema::hasTable('marketing_email_queue')) {
            Schema::create('marketing_email_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->foreignId('dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
                $table->string('recipient_email', 150);
                $table->string('sequence_key', 64);
                $table->string('step_key', 64);
                $table->json('meta')->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->timestamp('scheduled_at')->index();
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('abandoned_enquiry_sessions')) {
            Schema::create('abandoned_enquiry_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 64)->index();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
                $table->json('form_data')->nullable();
                $table->timestamp('last_activity_at');
                $table->timestamp('recovered_at')->nullable();
                $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('gdpr_data_requests')) {
            Schema::create('gdpr_data_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('email', 150)->index();
                $table->string('type', 32);
                $table->string('status', 32)->default('pending')->index();
                $table->string('download_path', 500)->nullable();
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
            });
        }

        if (! Schema::hasTable('dealer_api_keys')) {
            Schema::create('dealer_api_keys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('name');
                $table->string('key_prefix', 16)->index();
                $table->string('key_hash', 128);
                $table->json('permissions')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dealer_webhook_endpoints')) {
            Schema::create('dealer_webhook_endpoints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('url', 500);
                $table->string('secret', 64)->nullable();
                $table->json('events');
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dealer_webhook_deliveries')) {
            Schema::create('dealer_webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webhook_endpoint_id')->constrained('dealer_webhook_endpoints')->cascadeOnDelete();
                $table->string('event', 64);
                $table->json('payload');
                $table->string('status', 32);
                $table->unsignedSmallInteger('response_code')->nullable();
                $table->text('response_body')->nullable();
                $table->timestamp('attempted_at')->useCurrent();
            });
        }

        $permissions = [
            'admin.marketing.manage',
            'admin.compliance.manage',
            'dealer.trade_in.manage',
            'dealer.branding.manage',
            'dealer.dms.manage',
            'dealer.compliance.export',
        ];

        $rolePermissionService = app(RolePermissionService::class);
        foreach ($permissions as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(['admin.marketing.manage', 'admin.compliance.manage']);
        }

        $dealerRole = Role::where('name', 'dealer')->where('guard_name', 'web')->first();
        if ($dealerRole) {
            $dealerRole->givePermissionTo([
                'dealer.trade_in.manage',
                'dealer.branding.manage',
                'dealer.dms.manage',
                'dealer.compliance.export',
            ]);
        }

        $staffRole = Role::where('name', 'staff')->where('guard_name', 'web')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo([
                'dealer.trade_in.manage',
                'dealer.branding.manage',
                'dealer.compliance.export',
            ]);
        }

        $settings = app(PlatformSettingService::class);
        $settings->setGroup('finance', [
            'default_rate_pct' => '4.9',
            'min_rate_pct' => '2.9',
            'max_rate_pct' => '12.9',
            'default_term_months' => '60',
            'disclaimer_en' => 'Indicative monthly payment only. Final terms subject to lender approval.',
            'disclaimer_da' => 'Vejledende månedlig ydelse. Endelige vilkår afhænger af kreditgodkendelse.',
        ]);
        $settings->setGroup('marketing', [
            'enquiry_sequence_enabled' => 'true',
            'enquiry_day1_hours' => '24',
            'enquiry_day3_days' => '3',
            'abandoned_enquiry_enabled' => 'true',
            'abandoned_timeout_minutes' => '30',
            'whatsapp_auto_task' => 'true',
        ]);
        $settings->setGroup('reputation', [
            'google_places_api_key' => '',
        ]);
        $settings->setGroup('compliance', [
            'gdpr_export_enabled' => 'true',
            'data_retention_days' => '730',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_webhook_deliveries');
        Schema::dropIfExists('dealer_webhook_endpoints');
        Schema::dropIfExists('dealer_api_keys');
        Schema::dropIfExists('gdpr_data_requests');
        Schema::dropIfExists('abandoned_enquiry_sessions');
        Schema::dropIfExists('marketing_email_queue');
        Schema::dropIfExists('marketing_unsubscribes');
        Schema::dropIfExists('marketing_consent_logs');
        Schema::dropIfExists('dealer_domains');
        Schema::dropIfExists('trade_in_requests');

        if (Schema::hasTable('dealers')) {
            Schema::table('dealers', function (Blueprint $table) {
                foreach (['theme_secondary_color', 'theme_primary_color', 'google_place_id', 'google_review_url', 'finance_partner_url'] as $col) {
                    if (Schema::hasColumn('dealers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
