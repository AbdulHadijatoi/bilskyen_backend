<?php

namespace App\Http\Controllers;

use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;

class PublicPlatformController extends Controller
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function uiSettings(): JsonResponse
    {
        return $this->success([
            'language_switcher_enabled' => $this->platformSettingService->isLanguageSwitcherEnabled(),
        ]);
    }
}
