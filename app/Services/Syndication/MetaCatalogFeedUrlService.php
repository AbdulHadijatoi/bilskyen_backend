<?php

namespace App\Services\Syndication;

use App\Services\PlatformSettingService;
use Illuminate\Support\Str;

class MetaCatalogFeedUrlService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function ensurePlatformFeedToken(): string
    {
        $token = (string) $this->platformSettingService->get('marketing', 'meta_catalog_feed_token', '');
        if ($token === '') {
            $token = Str::random(48);
            $this->platformSettingService->set('marketing', 'meta_catalog_feed_token', $token);
        }

        return $token;
    }

    public function platformFeedUrl(): string
    {
        $token = $this->ensurePlatformFeedToken();

        return rtrim(config('app.url'), '/').'/api/v1/feeds/platform/'.$token.'/vehicles.csv';
    }

    public function dealerFeedUrl(string $token): string
    {
        return rtrim(config('app.url'), '/').'/api/v1/feeds/'.$token.'/vehicles.csv';
    }
}
