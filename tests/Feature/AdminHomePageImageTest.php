<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminHomePageController;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminHomePageImageTest extends TestCase
{
    public function test_home_page_image_upload_and_delete_routes_require_admin_auth(): void
    {
        $upload = app('router')->getRoutes()->match(
            Request::create('/api/v1/admin/home-page-content/images/upload', 'POST')
        );
        $delete = app('router')->getRoutes()->match(
            Request::create('/api/v1/admin/home-page-content/images/1', 'DELETE')
        );

        $this->assertStringContainsString(AdminHomePageController::class, $upload->getActionName());
        $this->assertStringContainsString('uploadImage', $upload->getActionName());
        $this->assertContains('auth:api', $upload->gatherMiddleware());
        $this->assertContains('role:admin', $upload->gatherMiddleware());

        $this->assertStringContainsString(AdminHomePageController::class, $delete->getActionName());
        $this->assertStringContainsString('deleteImage', $delete->getActionName());
        $this->assertContains('auth:api', $delete->gatherMiddleware());
        $this->assertContains('role:admin', $delete->gatherMiddleware());
    }

    public function test_unauthenticated_image_upload_is_rejected(): void
    {
        $this->postJson('/api/v1/admin/home-page-content/images/upload', [])
            ->assertUnauthorized();
    }
}
