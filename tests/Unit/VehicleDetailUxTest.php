<?php

namespace Tests\Unit;

use Tests\TestCase;

class VehicleDetailUxTest extends TestCase
{
    public function test_detail_page_has_share_dialog_and_location_map(): void
    {
        $source = file_get_contents(resource_path('views/vehicle-detail.blade.php'));

        $this->assertStringContainsString('<x-vehicle-share-dialog', $source);
        $this->assertStringContainsString('isVehicleDetailMapEnabled', $source);
        $this->assertStringContainsString('data-vehicle-map', $source);
        $this->assertStringContainsString('data-address="{{ $vehicleMapAddress }}"', $source);
        $this->assertStringContainsString('<x-vehicle-map-helpers', $source);
        $this->assertLessThan(
            strpos($source, '<!-- Right Sidebar -->'),
            strpos($source, 'data-vehicle-map-wrap'),
            'Location map should sit in the left column with the other listing details'
        );
        $this->assertLessThan(
            strpos($source, 'related-vehicles-heading'),
            strpos($source, 'data-vehicle-map-wrap')
        );

        $helpers = file_get_contents(resource_path('views/components/vehicle-map-helpers.blade.php'));
        $this->assertStringContainsString('isolation: isolate', $helpers);
        $this->assertStringContainsString('z-index: 0', $helpers);
        $this->assertStringNotContainsString('<x-listing-compare-tray', $source);
        $this->assertStringNotContainsString('<x-compare-helpers', $source);

        $share = file_get_contents(resource_path('views/components/vehicle-share-dialog.blade.php'));
        $this->assertStringContainsString('id="vehicle-share-dialog"', $share);
        $this->assertStringContainsString('popover="auto"', $share);
        $this->assertStringContainsString('popovertarget="vehicle-share-dialog"', $share);
        $this->assertStringContainsString('aria-label="{{ __(\'messages.pages.vehicles.detail.share\') }}"', $share);
        $this->assertStringNotContainsString('<span>{{ __(\'messages.pages.vehicles.detail.share\') }}</span>', $share);
        $this->assertStringContainsString('data-share-copy', $share);
        $this->assertStringContainsString('facebook.com/sharer', $share);
        $this->assertStringContainsString('wa.me/', $share);

        $en = include resource_path('lang/en/messages.php');
        $da = include resource_path('lang/da/messages.php');
        foreach (['share', 'share_title', 'share_copy', 'share_copied', 'location_map_title', 'call_dealer', 'call_seller'] as $key) {
            $this->assertNotEmpty($en['pages']['vehicles']['detail'][$key] ?? null, "Missing EN {$key}");
            $this->assertNotEmpty($da['pages']['vehicles']['detail'][$key] ?? null, "Missing DA {$key}");
        }
    }

    public function test_detail_page_has_mobile_sticky_cta_bar(): void
    {
        $source = file_get_contents(resource_path('views/vehicle-detail.blade.php'));

        $this->assertStringContainsString('id="vehicle-detail-mobile-cta"', $source);
        $this->assertStringContainsString('vehicle-detail-mobile-cta lg:hidden', $source);
        $this->assertStringContainsString('vehicle-detail-mobile-cta__btn--inquiry', $source);
        $this->assertStringContainsString('vehicle-detail-mobile-cta__actions--single', $source);
        $this->assertStringContainsString('env(safe-area-inset-bottom', $source);
        $this->assertStringContainsString('z-index: 60', $source);
        $this->assertStringContainsString('$contactPhoneAvailable', $source);
        $this->assertStringContainsString("window.bilskyenTrackFunnel('cta_click', { cta: 'phone' });", $source);
        $this->assertMatchesRegularExpression(
            '/showDealerPhoneAndCreateLead[\s\S]*?bilskyenTrackFunnel\(\'cta_click\', \{ cta: \'phone\' \}\)/',
            $source
        );
        $this->assertStringNotContainsString('vehicle-detail-mobile-cta__price', $source);
    }

    public function test_detail_page_lead_conversion_contact_actions_and_unified_dialog(): void
    {
        $detail = file_get_contents(resource_path('views/vehicle-detail.blade.php'));
        $contactActions = file_get_contents(resource_path('views/components/vehicle-contact-actions.blade.php'));
        $unifiedDialog = file_get_contents(resource_path('views/components/unified-enquiry-dialog.blade.php'));
        $enquiryController = file_get_contents(app_path('Http/Controllers/EnquiryController.php'));

        $this->assertStringContainsString('<x-vehicle-contact-actions', $detail);
        $this->assertStringContainsString('vehicle-contact-cta__btn--call', $contactActions);
        $this->assertStringContainsString('vehicle-contact-cta__btn--inquiry', $contactActions);
        $this->assertStringNotContainsString("openEnquiryDialog('exchange'", $detail);
        $this->assertStringNotContainsString("openEnquiryDialog('test-drive'", $detail);
        $this->assertStringNotContainsString("openEnquiryDialog('price-negotiation'", $detail);
        $this->assertStringNotContainsString('<x-enquiry-dialog', $detail);

        $this->assertStringContainsString('variant="desktop"', $detail);
        $this->assertStringContainsString('id="vehicle-detail-mobile-cta"', $detail);

        $pricingBlockPos = strpos($detail, '<!-- Mobile Pricing (below photos) -->');
        $financeCalcPos = strpos($detail, 'id="finance-calculator-mobile"');
        $this->assertNotFalse($pricingBlockPos);
        $this->assertNotFalse($financeCalcPos);
        $this->assertStringNotContainsString(
            '<x-vehicle-contact-actions',
            substr($detail, $pricingBlockPos, $financeCalcPos - $pricingBlockPos),
            'Inline contact actions should not appear in the mobile block below photos'
        );

        $this->assertStringNotContainsString('bg-background border-input', $contactActions);
        $this->assertStringNotContainsString('vehicle-contact-cta__btn--whatsapp', $contactActions);
        $this->assertStringNotContainsString('vehicle-whatsapp-fab', $detail);
        $this->assertStringNotContainsString('<x-vehicle-whatsapp-fab', $detail);
        $this->assertStringContainsString('vehicle-contact-cta__btn--call', $detail);

        $this->assertStringContainsString('<x-unified-enquiry-dialog', $detail);
        $this->assertStringContainsString('data-unified-type-select', $unifiedDialog);
        $this->assertStringContainsString('unified-enquiry-select', $unifiedDialog);
        $this->assertStringContainsString('unified-enquiry-label', $unifiedDialog);
        $this->assertStringNotContainsString('data-unified-type-radio', $unifiedDialog);
        $this->assertStringContainsString("@selected(\$typeKey === 'enquiry')", $unifiedDialog);
        $this->assertStringContainsString('z-[70]', $unifiedDialog);
        $this->assertStringContainsString('.unified-enquiry-dialog {', $detail);
        $this->assertStringContainsString('z-index: 70', $detail);

        $this->assertStringContainsString('vehicle-registration-status--desktop', $detail);
        $this->assertStringContainsString('vehicle-registration-status--mobile', $detail);
        $this->assertStringContainsString('variant="mobile"', $detail);
        $this->assertStringContainsString('vehicle-trust-report--mobile', $detail);
        $this->assertStringContainsString('vehicle-trust-report--desktop', $detail);
        $this->assertStringContainsString('variant="desktop"', $detail);

        $this->assertStringContainsString('$listingIsSold', $detail);
        $this->assertStringContainsString('browse_similar_cars', $detail);
        $this->assertStringContainsString('#related-vehicles-heading', $detail);

        $this->assertStringContainsString('rel="preconnect" href="https://cdn.jsdelivr.net"', $detail);
        $this->assertStringContainsString('embla-carousel.umd.js" defer', $detail);
        $this->assertStringContainsString('glightbox.min.js" defer', $detail);
        $this->assertStringContainsString('vehicleGalleryLightbox', $detail);

        $this->assertStringContainsString("'message' => 'nullable|string|max:5000'", $enquiryController);
        $this->assertStringContainsString("messages.dialogs.enquiry_default_message", $enquiryController);

        $en = include resource_path('lang/en/messages.php');
        $da = include resource_path('lang/da/messages.php');
        foreach (['browse_similar_cars', 'enquiry_type_label'] as $key) {
            $this->assertNotEmpty($en['pages']['vehicles']['detail'][$key] ?? null, "Missing EN {$key}");
            $this->assertNotEmpty($da['pages']['vehicles']['detail'][$key] ?? null, "Missing DA {$key}");
        }
        $this->assertNotEmpty($en['dialogs']['enquiry_default_message'] ?? null);
        $this->assertNotEmpty($da['dialogs']['enquiry_default_message'] ?? null);
    }
}
