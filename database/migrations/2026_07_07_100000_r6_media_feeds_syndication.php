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
        if (! Schema::hasColumn('vehicles', 'video_url')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('video_url', 500)->nullable()->after('view_3d_url');
                $table->string('video_provider', 32)->nullable()->after('video_url');
            });
        }

        if (! Schema::hasTable('dealer_feed_tokens')) {
            Schema::create('dealer_feed_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('name')->default('Default');
                $table->string('token', 64)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dealer_syndication_settings')) {
            Schema::create('dealer_syndication_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->string('provider_key', 64);
                $table->boolean('enabled')->default(false);
                $table->json('field_mapping')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();
                $table->unique(['dealer_id', 'provider_key']);
            });
        }

        if (! Schema::hasTable('syndication_logs')) {
            Schema::create('syndication_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->string('provider_key', 64)->index();
                $table->string('action', 32)->default('sync');
                $table->string('status', 32)->index();
                $table->string('external_listing_id')->nullable();
                $table->text('message')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        $permissions = [
            'dealer.feeds.export',
            'dealer.syndication.manage',
            'admin.syndication.view',
            'admin.syndication.manage',
        ];

        $rolePermissionService = app(RolePermissionService::class);
        foreach ($permissions as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(['admin.syndication.view', 'admin.syndication.manage']);
        }

        $dealerRole = Role::where('name', 'dealer')->where('guard_name', 'web')->first();
        if ($dealerRole) {
            $dealerRole->givePermissionTo(['dealer.feeds.export', 'dealer.syndication.manage']);
        }

        $staffRole = Role::where('name', 'staff')->where('guard_name', 'web')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo(['dealer.feeds.export', 'dealer.syndication.manage']);
        }

        $settings = app(PlatformSettingService::class);
        $settings->setGroup('media', [
            'min_images_before_publish' => '0',
            'max_image_upload_mb' => '10',
            'watermark_enabled' => 'false',
            'watermark_opacity' => '40',
        ]);
        $settings->setGroup('syndication', [
            'sftp_enabled' => 'false',
            'sftp_host' => '',
            'sftp_port' => '22',
            'sftp_username' => '',
            'sftp_password' => '',
            'sftp_remote_path' => '/feeds',
            'platform_feed_format' => 'xml',
            'auto_sync_on_publish' => 'true',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('syndication_logs');
        Schema::dropIfExists('dealer_syndication_settings');
        Schema::dropIfExists('dealer_feed_tokens');

        if (Schema::hasColumn('vehicles', 'video_url')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn(['video_url', 'video_provider']);
            });
        }
    }
};
