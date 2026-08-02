<?php

namespace App\Http\Controllers;

use App\Exceptions\AiGenerationException;
use App\Services\AiService;
use App\Services\CarAdvisorService;
use App\Services\SearchQueryLogService;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CarAdvisorController extends Controller
{
    private const MAX_MESSAGE_LENGTH = 2000;

    private const MAX_HISTORY_TURNS = 8;

    private const MAX_HISTORY_MESSAGE_LENGTH = 1000;

    public function __construct(
        private AiService $aiService,
        private CarAdvisorService $carAdvisorService,
        private SuggestionService $suggestionService,
        private SearchQueryLogService $searchQueryLogService,
    ) {}

    /**
     * POST /api/v1/ai/car-advisor
     */
    public function advise(Request $request): JsonResponse
    {
        try {
            if (! $this->aiService->isGloballyEnabled()) {
                return $this->error(__('messages.api.ai_not_enabled'), [], 422);
            }

            $data = $request->validate([
                'message' => 'required|string|max:'.self::MAX_MESSAGE_LENGTH,
                'locale' => 'sometimes|string|in:da,en',
                'history' => 'sometimes|array|max:'.self::MAX_HISTORY_TURNS,
                'history.*.role' => 'required_with:history|string|in:user,assistant',
                'history.*.content' => 'required_with:history|string|max:'.self::MAX_HISTORY_MESSAGE_LENGTH,
            ]);

            $locale = $data['locale'] ?? app()->getLocale();

            $this->searchQueryLogService->log(
                surface: 'advisor',
                query: $data['message'],
                locale: $locale,
            );

            $result = $this->carAdvisorService->advise(
                message: $data['message'],
                locale: $locale,
                history: $data['history'] ?? [],
            );

            return $this->success($result);
        } catch (AiGenerationException $e) {
            return $this->error($e->getMessage(), [], $e->statusCode());
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }

    /**
     * GET /api/v1/ai/car-advisor/examples
     */
    public function examples(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => 'sometimes|string|in:da,en',
        ]);

        return $this->success([
            'examples' => $this->suggestionService->examplePrompts(
                $data['locale'] ?? app()->getLocale()
            ),
            'enabled' => $this->aiService->isGloballyEnabled(),
        ]);
    }
}
