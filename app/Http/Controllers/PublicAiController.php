<?php

namespace App\Http\Controllers;

use App\Constants\AiGenerationTask;
use App\Exceptions\AiGenerationException;
use App\Services\Ai\AiGuardrailService;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicAiController extends Controller
{
    private const MAX_CONTEXT_BYTES = 16384;

    private const MAX_CONTEXT_KEYS = 30;

    public function __construct(
        private AiService $aiService,
        private AiGuardrailService $guardrails,
    ) {}

    public function generateListingDescription(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'locale' => 'sometimes|string|in:da,en',
                'context' => 'required|array|max:'.self::MAX_CONTEXT_KEYS,
            ]);

            $encoded = json_encode($data['context']);
            if ($encoded !== false && strlen($encoded) > self::MAX_CONTEXT_BYTES) {
                throw ValidationException::withMessages([
                    'context' => [__('messages.api.ai_context_too_large')],
                ]);
            }

            $result = $this->aiService->generateForPublic(
                task: AiGenerationTask::LISTING_DESCRIPTION,
                context: $this->guardrails->allowlistContext(
                    $data['context'],
                    AiGuardrailService::LISTING_CONTEXT_KEYS
                ),
                locale: $data['locale'] ?? app()->getLocale(),
            );

            return $this->success($result);
        } catch (AiGenerationException $e) {
            return $this->error($e->getMessage(), [], $e->statusCode());
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }
}
