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
            'faq_page_enabled' => $this->platformSettingService->isFaqPageEnabled(),
            'faq_chatbot_enabled' => $this->platformSettingService->isFaqChatbotEnabled(),
        ]);
    }
}
