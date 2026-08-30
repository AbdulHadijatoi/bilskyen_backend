<?php

namespace Tests\Unit;

use App\Services\Marketing\TrafficAttributionService;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrafficAttributionServiceTest extends TestCase
{
    public function test_classifies_meta_from_fbclid_utm_and_referrer(): void
    {
        $service = new TrafficAttributionService();

        $this->assertSame(TrafficAttributionService::SOURCE_META, $service->classify(null, 'IwAR123', null));
        $this->assertSame(TrafficAttributionService::SOURCE_META, $service->classify('facebook', null, null));
        $this->assertSame(TrafficAttributionService::SOURCE_META, $service->classify('ig', null, null));
        $this->assertSame(
            TrafficAttributionService::SOURCE_META,
            $service->classify(null, null, 'https://l.facebook.com/l.php?u=https://example.test')
        );
        $this->assertSame(
            TrafficAttributionService::SOURCE_OTHER,
            $service->classify('google', null, 'https://www.google.com/search')
        );
    }

    public function test_from_request_captures_utm_and_fbclid(): void
    {
        $service = new TrafficAttributionService();
        $request = Request::create('https://example.test/biler/foo', 'GET', [
            'fbclid' => 'abc',
            'utm_source' => 'facebook',
            'utm_campaign' => 'spring-sale',
            'utm_medium' => 'paid',
        ]);
        $request->headers->set('referer', 'https://m.facebook.com/');

        $snapshot = $service->fromRequest($request);

        $this->assertSame(TrafficAttributionService::SOURCE_META, $snapshot['traffic_source']);
        $this->assertSame('facebook', $snapshot['utm_source']);
        $this->assertSame('spring-sale', $snapshot['utm_campaign']);
        $this->assertSame('paid', $snapshot['utm_medium']);
        $this->assertSame('abc', $snapshot['fbclid']);
    }

    public function test_lead_attributes_use_last_touch(): void
    {
        $service = new TrafficAttributionService();
        $request = Request::create('https://example.test/biler/foo', 'GET', [
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'meta-apr',
        ]);
        $request->headers->set('referer', 'https://www.facebook.com/');
        $request->setLaravelSession($this->app['session']->driver());

        $service->capture($request);
        $lead = $service->leadAttributes($request);

        $this->assertSame('facebook', $lead['utm_source']);
        $this->assertSame('cpc', $lead['utm_medium']);
        $this->assertSame('meta-apr', $lead['utm_campaign']);
        $this->assertSame(TrafficAttributionService::SOURCE_META, $lead['traffic_source']);
        $this->assertNotEmpty($lead['referrer_url']);
    }

    public function test_lead_attributes_classify_meta_from_fbclid_in_session(): void
    {
        $service = new TrafficAttributionService();
        $request = Request::create('https://example.test/biler/foo', 'GET', [
            'fbclid' => 'IwAR123',
        ]);
        $request->setLaravelSession($this->app['session']->driver());

        $service->capture($request);
        $lead = $service->leadAttributes($request);

        $this->assertSame(TrafficAttributionService::SOURCE_META, $lead['traffic_source']);
    }
}
