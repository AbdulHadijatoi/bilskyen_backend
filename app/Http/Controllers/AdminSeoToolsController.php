<?php

namespace App\Http\Controllers;

use App\Services\PlatformSettingService;
use App\Services\Seo\SchemaBuilderService;
use App\Services\Seo\SeoAuditService;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminSeoToolsController extends Controller
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
        private SeoAuditService $auditService,
        private SchemaBuilderService $schemaBuilder,
        private SeoService $seoService
    ) {}

    public function robotsShow(): JsonResponse
    {
        return $this->success([
            'mode' => $this->platformSettingService->get('seo', 'robots_mode', 'default'),
            'custom_body' => $this->platformSettingService->get('seo', 'robots_custom_body', ''),
            'preview' => $this->seoService->getRobotsTxt(),
        ]);
    }

    public function robotsUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode' => 'required|in:default,custom',
            'custom_body' => 'nullable|string',
        ]);

        $this->platformSettingService->setGroup('seo', [
            'robots_mode' => $data['mode'],
            'robots_custom_body' => $data['custom_body'] ?? '',
        ]);

        Cache::forget('robots_txt');

        return $this->success(['preview' => $this->seoService->getRobotsTxt()]);
    }

    public function cookieConsentShow(): JsonResponse
    {
        return $this->success([
            'enabled' => (bool) $this->platformSettingService->get('seo', 'cookie_consent_enabled', false),
            'text_en' => $this->platformSettingService->get('seo', 'cookie_consent_text_en', ''),
            'text_da' => $this->platformSettingService->get('seo', 'cookie_consent_text_da', ''),
        ]);
    }

    public function cookieConsentUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'text_en' => 'nullable|string',
            'text_da' => 'nullable|string',
        ]);

        $this->platformSettingService->setGroup('seo', [
            'cookie_consent_enabled' => $data['enabled'] ? 'true' : 'false',
            'cookie_consent_text_en' => $data['text_en'] ?? '',
            'cookie_consent_text_da' => $data['text_da'] ?? '',
        ]);

        return $this->success($data);
    }

    public function audit(): JsonResponse
    {
        return $this->success($this->auditService->run());
    }

    public function schemaPresets(): JsonResponse
    {
        return $this->success($this->schemaBuilder->presets());
    }

    public function buildSchema(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string|max:64',
            'fields' => 'required|array',
        ]);

        return $this->success($this->schemaBuilder->build($data['type'], $data['fields']));
    }
}
