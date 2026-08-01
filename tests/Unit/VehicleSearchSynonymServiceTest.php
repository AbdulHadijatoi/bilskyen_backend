<?php

namespace Tests\Unit;

use App\Services\VehicleSearchSynonymService;
use Tests\TestCase;

class VehicleSearchSynonymServiceTest extends TestCase
{
    public function test_expands_danish_electric_slang(): void
    {
        $service = new VehicleSearchSynonymService;
        $expanded = $service->expand('billig elbil i Aarhus');

        $this->assertStringContainsString('Electric', $expanded);
        $this->assertStringContainsString('cheap', $expanded);
        $this->assertStringContainsString('Aarhus', $expanded);
    }

    public function test_example_queries_are_locale_aware(): void
    {
        $service = new VehicleSearchSynonymService;
        $da = $service->exampleQueries('da');
        $en = $service->exampleQueries('en');

        $this->assertNotEmpty($da);
        $this->assertNotEmpty($en);
        $this->assertStringContainsString('Elbil', $da[0]);
        $this->assertStringContainsString('Electric', $en[0]);
    }
}
