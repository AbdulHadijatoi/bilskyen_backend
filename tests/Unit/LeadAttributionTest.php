<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Services\Marketing\TrafficAttributionService;
use Tests\TestCase;

class LeadAttributionTest extends TestCase
{
    public function test_effective_traffic_source_uses_stored_value_when_present(): void
    {
        $lead = new Lead([
            'traffic_source' => TrafficAttributionService::SOURCE_META,
            'utm_source' => 'google',
        ]);

        $this->assertSame(TrafficAttributionService::SOURCE_META, $lead->effective_traffic_source);
    }

    public function test_effective_traffic_source_derives_meta_from_legacy_utm_source(): void
    {
        $lead = new Lead([
            'utm_source' => 'facebook',
        ]);

        $this->assertSame(TrafficAttributionService::SOURCE_META, $lead->effective_traffic_source);
    }

    public function test_effective_traffic_source_defaults_to_other_without_signals(): void
    {
        $lead = new Lead();

        $this->assertSame(TrafficAttributionService::SOURCE_OTHER, $lead->effective_traffic_source);
    }
}
