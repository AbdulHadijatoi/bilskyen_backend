<?php

namespace Tests\Unit;

use App\Models\Dealer;
use App\Services\DealerVehicleAddressService;
use PHPUnit\Framework\TestCase;

class DealerVehicleAddressServiceTest extends TestCase
{
    public function test_it_overrides_imported_address_with_dealer_profile_address(): void
    {
        $dealer = new Dealer([
            'address' => 'Main Street 12',
            'postcode' => '2100',
            'city' => 'Copenhagen',
        ]);

        $payload = (new DealerVehicleAddressService)->applyToPayload([
            'address' => 'Spreadsheet Address 99',
            'postcode' => '9999',
        ], $dealer);

        $this->assertSame('Main Street 12, 2100 Copenhagen', $payload['address']);
        $this->assertSame('2100', $payload['postcode']);
    }

    public function test_it_clears_payload_address_when_dealer_profile_has_no_address(): void
    {
        $dealer = new Dealer;

        $payload = (new DealerVehicleAddressService)->applyToPayload([
            'address' => 'Submitted Address',
            'postcode' => '1234',
        ], $dealer);

        $this->assertNull($payload['address']);
        $this->assertNull($payload['postcode']);
    }
}
