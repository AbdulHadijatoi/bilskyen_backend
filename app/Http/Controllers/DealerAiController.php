<?php

namespace App\Http\Controllers;

use App\Constants\AiGenerationTask;
use App\Exceptions\AiGenerationException;
use App\Services\AiService;
use App\Services\DealerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DealerAiController extends Controller
{
    private const MAX_CONTEXT_BYTES = 32768;

    private const MAX_CONTEXT_KEYS = 50;

    public function __construct(
        private AiService $aiService,
        private DealerContextService $dealerContextService,
    ) {}

    public function config(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());

        return $this->success($this->aiService->dealerConfig($dealer));
    }

    public function generate(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'task' => ['required', 'string', Rule::in(AiGenerationTask::values())],
                'locale' => 'sometimes|string|in:da,en',
                'context' => 'required|array|max:'.self::MAX_CONTEXT_KEYS,
                'context_type' => 'sometimes|string|max:64',
                'context_id' => 'sometimes|integer',
            ]);

            $this->assertContextSize($data['context']);

            $dealer = $this->dealerContextService->requireDealer($request->user());

            $result = $this->aiService->generate(
                task: $data['task'],
                context: $data['context'],
                user: $request->user(),
                dealer: $dealer,
                locale: $data['locale'] ?? app()->getLocale(),
                contextType: $data['context_type'] ?? null,
                contextId: $data['context_id'] ?? null,
            );

            return $this->success($result);
        } catch (AiGenerationException $e) {
            return $this->error($e->getMessage(), [], $e->statusCode());
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function assertContextSize(array $context): void
    {
        $encoded = json_encode($context);
        if ($encoded !== false && strlen($encoded) > self::MAX_CONTEXT_BYTES) {
            throw ValidationException::withMessages([
                'context' => [__('messages.api.ai_context_too_large')],
            ]);
        }
    }
}
