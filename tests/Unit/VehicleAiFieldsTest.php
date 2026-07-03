<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use Tests\TestCase;

class VehicleAiFieldsTest extends TestCase
{
    public function test_vehicle_fillable_includes_ai_fields(): void
    {
        $fillable = (new Vehicle)->getFillable();

        foreach (['highlights', 'meta_title', 'meta_description'] as $column) {
            $this->assertContains($column, $fillable, 'Missing fillable: '.$column);
        }
    }
}
