<?php

namespace Tests\Unit;

use App\Models\Enquiry;
use App\Models\Lead;
use App\Models\LeadCategory;
use App\Models\User;
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

    public function test_resolve_buyer_display_name_prefers_buyer_user(): void
    {
        $lead = new Lead();
        $lead->setRelation('buyerUser', new User(['name' => 'Jane Buyer']));

        $this->assertSame('Jane Buyer', $lead->resolveBuyerDisplayName());
    }

    public function test_resolve_buyer_display_name_falls_back_to_enquiry_contact_fields(): void
    {
        $lead = new Lead();
        $lead->setRelation('enquiry', new Enquiry([
            'name' => 'Guest',
            'phone' => '+45 12 34 56 78',
            'email' => 'buyer@example.com',
        ]));

        $this->assertSame('+45 12 34 56 78', $lead->resolveBuyerDisplayName());
    }

    public function test_resolve_buyer_display_name_uses_lead_category_when_contact_missing(): void
    {
        $lead = new Lead();
        $lead->setRelation('enquiry', new Enquiry(['name' => 'Guest']));
        $lead->setRelation('leadCategory', new LeadCategory(['name' => 'Phone Number Revealed']));

        $this->assertSame('Phone Number Revealed', $lead->resolveBuyerDisplayName());
    }

    public function test_resolve_buyer_display_name_returns_null_when_no_identity(): void
    {
        $lead = new Lead();

        $this->assertNull($lead->resolveBuyerDisplayName());
    }
}
