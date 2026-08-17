<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\Marketing\MetaConversionsApiService;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MetaConversionsApiServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pixel_id_falls_back_to_live_id(): void
    {
        $settings = $this->settingsMock([
            'meta_pixel_id' => '',
            'meta_pixel_enabled' => false,
        ]);

        $service = new MetaConversionsApiService($settings);

        $this->assertSame(MetaConversionsApiService::FALLBACK_PIXEL_ID, $service->pixelId());
        $this->assertTrue($service->isBrowserEnabled());
        $this->assertFalse($service->isEnabled());
    }

    public function test_settings_pixel_id_wins_over_fallback(): void
    {
        $settings = $this->settingsMock([
            'meta_pixel_id' => '111222333',
            'meta_pixel_enabled' => true,
        ]);

        $service = new MetaConversionsApiService($settings);

        $this->assertSame('111222333', $service->pixelId());
        $this->assertTrue($service->isBrowserEnabled());
        $this->assertTrue($service->isEnabled());
    }

    public function test_domain_verification_code_is_trimmed(): void
    {
        $settings = $this->settingsMock([
            'meta_domain_verification' => '  abc123  ',
        ]);

        $service = new MetaConversionsApiService($settings);

        $this->assertSame('abc123', $service->domainVerificationCode());
    }

    public function test_track_lead_sends_numeric_vehicle_id(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $settings = $this->settingsMock([
            'meta_pixel_id' => '999',
            'meta_pixel_enabled' => true,
            'meta_capi_access_token' => 'capi-token',
            'meta_capi_test_event_code' => '',
        ]);

        $vehicle = new Vehicle(['title' => 'Mercedes GLE', 'price' => 479800]);
        $vehicle->id = 1554;

        $service = new MetaConversionsApiService($settings);
        $service->trackLead(
            $vehicle,
            'evt-lead-1',
            'https://bilskyen.dk/biler/mercedes-gle',
            '1.1.1.1',
            'PHPUnit',
            'buyer@example.com',
            '12345678'
        );

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $event = $payload['data'][0] ?? [];

            return $request->url() === 'https://graph.facebook.com/v21.0/999/events?access_token=capi-token'
                && ($event['event_name'] ?? null) === 'Lead'
                && ($event['event_id'] ?? null) === 'evt-lead-1'
                && ($event['custom_data']['content_ids'] ?? null) === ['1554']
                && ($event['custom_data']['content_type'] ?? null) === 'vehicle'
                && ($event['custom_data']['currency'] ?? null) === 'DKK';
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function settingsMock(array $values): PlatformSettingService
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')->andReturnUsing(function (string $group, string $key, mixed $default = null) use ($values) {
            $this->assertSame('marketing', $group);

            return array_key_exists($key, $values) ? $values[$key] : $default;
        });

        return $settings;
    }
}
