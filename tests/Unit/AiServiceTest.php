<?php

namespace Tests\Unit;

use App\Constants\AiGenerationTask;
use App\Exceptions\AiGenerationException;
use App\Models\AiPromptTemplate;
use App\Services\AiService;
use ReflectionMethod;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    public function test_render_prompts_replaces_placeholders(): void
    {
        $template = new AiPromptTemplate([
            'key' => AiGenerationTask::VEHICLE_DESCRIPTION,
            'name' => 'Test',
            'system_prompt' => 'Locale={{locale}}',
            'user_prompt_template' => "Data:\n{{context}}\nJSON:\n{{context_json}}",
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service = app(AiService::class);
        $method = new ReflectionMethod(AiService::class, 'renderPrompts');
        $method->setAccessible(true);

        [$system, $user] = $method->invoke($service, $template, ['make' => 'Volvo', 'model' => 'XC60'], 'da');

        $this->assertStringContainsString('Locale=da', $system);
        $this->assertStringContainsString('Make: Volvo', $user);
        $this->assertStringContainsString('"make": "Volvo"', $user);
    }

    public function test_ai_generation_exception_carries_status_code(): void
    {
        $exception = new AiGenerationException('Quota exceeded', 422);

        $this->assertSame(422, $exception->statusCode());
        $this->assertSame('Quota exceeded', $exception->getMessage());
    }
}
