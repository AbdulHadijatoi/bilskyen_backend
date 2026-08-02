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

    public function test_example_queries_delegate_to_suggestion_service(): void
    {
        $service = new VehicleSearchSynonymService;
        $da = $service->exampleQueries('da');
        $en = $service->exampleQueries('en');

        $this->assertNotEmpty($da);
        $this->assertNotEmpty($en);
    }
}
