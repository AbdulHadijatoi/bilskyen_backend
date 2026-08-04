<?php

namespace Tests\Unit;

use App\Services\VehicleSearchSynonymService;
use Tests\TestCase;

class VehicleSearchSynonymServiceTest extends TestCase
{
    public function test_expands_danish_electric_slang_to_dmr_el(): void
    {
        $service = new VehicleSearchSynonymService;
        $expanded = $service->expand('billig elbil i Aarhus');

        $this->assertStringContainsString('El', $expanded);
        $this->assertStringContainsString('billig', $expanded);
        $this->assertStringContainsString('Aarhus', $expanded);
        $this->assertStringNotContainsString('Electric', $expanded);
    }

    public function test_expands_benzin_and_stationcar_to_catalog_names(): void
    {
        $service = new VehicleSearchSynonymService;
        $expanded = $service->expand('benzin stationcar med automatgear');

        $this->assertStringContainsString('Benzin', $expanded);
        $this->assertStringContainsString('Stationcar', $expanded);
        $this->assertStringContainsString('Automatic', $expanded);
        $this->assertStringNotContainsString('Petrol', $expanded);
        $this->assertStringNotContainsString('Estate', $expanded);
    }

    public function test_english_fuel_aliases_map_to_danish_catalog(): void
    {
        $service = new VehicleSearchSynonymService;

        $this->assertSame('El', $service->canonicalFor('electric'));
        $this->assertSame('Benzin', $service->canonicalFor('petrol'));
        $this->assertSame('Stationcar', $service->canonicalFor('estate'));
        $this->assertContains('elbil', $service->equivalentTerms('Electric'));
        $this->assertContains('benzin', $service->equivalentTerms('Petrol'));
        $this->assertContains('stationcar', $service->equivalentTerms('Estate'));
    }

    public function test_example_queries_delegate_to_suggestion_service(): void
    {
        $service = new VehicleSearchSynonymService;
        $da = $service->exampleQueries('da');
        $en = $service->exampleQueries('en');

        $this->assertNotEmpty($da);
        $this->assertNotEmpty($en);
    }
}
