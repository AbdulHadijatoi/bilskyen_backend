<?php

namespace Tests\Unit;

use App\Constants\AiGenerationTask;
use Tests\TestCase;

class AiConstantsTest extends TestCase
{
    public function test_ai_generation_tasks_include_vehicle_description(): void
    {
        $this->assertContains(AiGenerationTask::VEHICLE_DESCRIPTION, AiGenerationTask::values());
        $this->assertTrue(AiGenerationTask::isValid('vehicle_description'));
    }

    public function test_ai_generation_tasks_include_search_parse(): void
    {
        $this->assertContains(AiGenerationTask::SEARCH_PARSE, AiGenerationTask::values());
        $this->assertTrue(AiGenerationTask::isValid('search_parse'));
    }

    public function test_ai_config_defaults(): void
    {
        $this->assertNotEmpty(config('ai.providers.openai.default_model'));
        $this->assertSame('deepseek-v4-flash', config('ai.providers.deepseek.default_model'));
        $this->assertGreaterThan(0, config('ai.default_max_tokens'));
    }
}
