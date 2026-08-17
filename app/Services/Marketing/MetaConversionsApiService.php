<?php

namespace App\Services\Marketing;

use App\Models\Vehicle;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaConversionsApiService
{
    public const FALLBACK_PIXEL_ID = '1904616770388925';

    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    /**
     * Admin toggle + pixel ID: used for CAPI and catalog-guide status.
     */
    public function isEnabled(): bool
    {
        return filter_var(
            $this->platformSettingService->get('marketing', 'meta_pixel_enabled', false),
            FILTER_VALIDATE_BOOLEAN
        ) && $this->pixelId() !== '';
    }

    /**
     * Browser Pixel / ViewContent / Lead. Always on when a Pixel ID is resolvable
     * (settings or the live fallback) so retargeting does not depend on CAPI setup.
     */
    public function isBrowserEnabled(): bool
    {
        return $this->pixelId() !== '';
    }

    public function pixelId(): string
    {
        $id = trim((string) $this->platformSettingService->get('marketing', 'meta_pixel_id', ''));

        return $id !== '' ? $id : self::FALLBACK_PIXEL_ID;
    }

    public function domainVerificationCode(): string
    {
        return trim((string) $this->platformSettingService->get('marketing', 'meta_domain_verification', ''));
    }

    public function accessToken(): string
    {
        return trim((string) $this->platformSettingService->get('marketing', 'meta_capi_access_token', ''));
    }

    public function testEventCode(): string
    {
        return trim((string) $this->platformSettingService->get('marketing', 'meta_capi_test_event_code', ''));
    }

    /**
     * @param  array<string, mixed>  $customData
     * @param  array<string, mixed>  $userData
     */
    public function sendEvent(
        string $eventName,
        string $eventId,
        string $eventSourceUrl,
        array $customData = [],
        array $userData = [],
    ): void {
        if (! $this->isEnabled() || $this->accessToken() === '') {
            return;
        }

        $payload = [
            'data' => [[
                'event_name' => $eventName,
                'event_time' => time(),
                'event_id' => $eventId,
                'event_source_url' => $eventSourceUrl,
                'action_source' => 'website',
                'user_data' => array_filter([
                    'client_ip_address' => $userData['client_ip_address'] ?? null,
                    'client_user_agent' => $userData['client_user_agent'] ?? null,
                    'em' => isset($userData['email']) ? hash('sha256', strtolower(trim((string) $userData['email']))) : null,
                    'ph' => isset($userData['phone']) ? hash('sha256', preg_replace('/\D+/', '', (string) $userData['phone']) ?? '') : null,
                ], fn ($v) => $v !== null && $v !== ''),
                'custom_data' => $customData,
            ]],
        ];

        $testCode = $this->testEventCode();
        if ($testCode !== '') {
            $payload['test_event_code'] = $testCode;
        }

        try {
            $response = Http::timeout(5)->asJson()->post(
                'https://graph.facebook.com/v21.0/'.$this->pixelId().'/events?access_token='.urlencode($this->accessToken()),
                $payload
            );

            if (! $response->successful()) {
                Log::warning('Meta CAPI event failed', [
                    'event' => $eventName,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI event exception', [
                'event' => $eventName,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function trackViewContent(Vehicle $vehicle, string $eventId, string $url, ?string $ip, ?string $ua): void
    {
        $this->sendEvent('ViewContent', $eventId, $url, [
            'content_ids' => [(string) $vehicle->id],
            'content_type' => 'vehicle',
            'content_name' => $vehicle->title,
            'value' => (float) ($vehicle->price ?? 0),
            'currency' => 'DKK',
        ], [
            'client_ip_address' => $ip,
            'client_user_agent' => $ua,
        ]);
    }

    public function trackLead(Vehicle $vehicle, string $eventId, string $url, ?string $ip, ?string $ua, ?string $email = null, ?string $phone = null): void
    {
        $this->sendEvent('Lead', $eventId, $url, [
            'content_ids' => [(string) $vehicle->id],
            'content_type' => 'vehicle',
            'content_name' => $vehicle->title,
            'value' => (float) ($vehicle->price ?? 0),
            'currency' => 'DKK',
        ], [
            'client_ip_address' => $ip,
            'client_user_agent' => $ua,
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    public function newEventId(): string
    {
        return (string) Str::uuid();
    }
}
