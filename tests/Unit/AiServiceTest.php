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
        $this->assertStringContainsString('"make":"Volvo"', $user);
    }

    public function test_public_prompts_include_preamble_and_fenced_user_message(): void
    {
        $template = new AiPromptTemplate([
            'key' => AiGenerationTask::CAR_ADVISOR_PROFILE,
            'name' => 'Test',
            'system_prompt' => 'You extract JSON.',
            'user_prompt_template' => "Locale: {{locale}}\n\n{{context}}",
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guardrails = app(\App\Services\Ai\AiGuardrailService::class);
        $context = $guardrails->preparePublicContext(AiGenerationTask::CAR_ADVISOR_PROFILE, [
            'user_message' => 'elbil under 200000',
            'output_schema' => 'JSON object with keys',
        ]);

        $service = app(AiService::class);
        $method = new ReflectionMethod(AiService::class, 'renderPrompts');
        $method->setAccessible(true);

        [$system, $user] = $method->invoke($service, $template, $context, 'da');
        $system = $guardrails->publicSystemPreamble()."\n\n".$system;

        $this->assertStringContainsString('untrusted user data', $system);
        $this->assertStringContainsString('<<<UNTRUSTED', $user);
        $this->assertStringContainsString('elbil under 200000', $user);
        $this->assertStringContainsString('JSON object with keys', $user);
        $this->assertDoesNotMatchRegularExpression('/<<<UNTRUSTED\s+JSON object with keys/', $user);
    }

    public function test_completion_options_use_task_limits(): void
    {
        $service = app(AiService::class);
        $method = new ReflectionMethod(AiService::class, 'completionOptionsForTask');
        $method->setAccessible(true);

        $search = $method->invoke($service, AiGenerationTask::SEARCH_PARSE);
        $this->assertSame(180, $search['max_tokens']);
        $this->assertEquals(0.0, $search['temperature']);

        $dealer = $method->invoke($service, AiGenerationTask::VEHICLE_DESCRIPTION);
        $this->assertSame([], $dealer);
    }

    public function test_ai_generation_exception_carries_status_code(): void
    {
        $exception = new AiGenerationException('Quota exceeded', 422);

        $this->assertSame(422, $exception->statusCode());
        $this->assertSame('Quota exceeded', $exception->getMessage());
    }
}
