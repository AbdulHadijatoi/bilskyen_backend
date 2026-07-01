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
        if (! Schema::hasTable('cms_post_categories')) {
            Schema::create('cms_post_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_media')) {
            Schema::create('cms_media', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('path');
                $table->string('disk', 32)->default('public');
                $table->string('mime_type', 128)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('alt_text')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_posts')) {
            Schema::create('cms_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->nullable()->constrained('cms_post_categories')->nullOnDelete();
                $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('featured_media_id')->nullable()->constrained('cms_media')->nullOnDelete();
                $table->string('slug')->unique();
                $table->string('title');
                $table->text('excerpt')->nullable();
                $table->longText('content_html')->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamp('scheduled_at')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('og_image')->nullable();
                $table->string('robots', 100)->nullable();
                $table->string('canonical_url')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('landing_pages')) {
            Schema::create('landing_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->json('blocks')->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamp('scheduled_at')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('robots', 100)->nullable();
                $table->string('og_title')->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cms_content_versions')) {
            Schema::create('cms_content_versions', function (Blueprint $table) {
                $table->id();
                $table->string('versionable_type');
                $table->unsignedBigInteger('versionable_id');
                $table->unsignedInteger('version_number');
                $table->json('snapshot');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['versionable_type', 'versionable_id']);
            });
        }

        if (! Schema::hasTable('seo_redirects')) {
            Schema::create('seo_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('from_path')->unique();
                $table->string('to_path');
                $table->unsignedSmallInteger('redirect_type')->default(301);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('hit_count')->default(0);
                $table->timestamps();
            });
        }

        $permissions = [
            'admin.cms.posts.view',
            'admin.cms.posts.manage',
            'admin.cms.landing.view',
            'admin.cms.landing.manage',
            'admin.cms.media.view',
            'admin.cms.media.manage',
            'admin.seo.redirects.view',
            'admin.seo.redirects.manage',
            'admin.seo.tools.view',
            'admin.seo.tools.manage',
        ];

        $rolePermissionService = app(RolePermissionService::class);
        foreach ($permissions as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $settings = app(PlatformSettingService::class);
        $settings->setGroup('seo', [
            'robots_mode' => 'default',
            'robots_custom_body' => '',
            'cookie_consent_enabled' => 'false',
            'cookie_consent_text_en' => 'We use cookies to improve your experience. By continuing, you accept our use of cookies.',
            'cookie_consent_text_da' => 'Vi bruger cookies for at forbedre din oplevelse. Ved at fortsætte accepterer du vores brug af cookies.',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_content_versions');
        Schema::dropIfExists('cms_posts');
        Schema::dropIfExists('landing_pages');
        Schema::dropIfExists('cms_media');
        Schema::dropIfExists('cms_post_categories');
        Schema::dropIfExists('seo_redirects');
    }
};
