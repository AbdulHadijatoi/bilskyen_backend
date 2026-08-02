<?php

namespace App\Http\Controllers;

use App\Exceptions\AiGenerationException;
use App\Models\SavedSearch;
use App\Services\AiSearchParseService;
use App\Services\AiService;
use App\Services\AuthService;
use App\Services\SearchQueryLogService;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiSearchController extends Controller
{
    private const MAX_QUERY_LENGTH = 500;

    public function __construct(
        private AiSearchParseService $aiSearchParseService,
        private SuggestionService $suggestionService,
        private SearchQueryLogService $searchQueryLogService,
        private AuthService $authService,
        private AiService $aiService,
    ) {}

    /**
     * POST /api/v1/ai/search-parse
     */
    public function parse(Request $request): JsonResponse
    {
        try {
            if (! $this->aiService->isGloballyEnabled()) {
                return $this->error(__('messages.api.ai_not_enabled'), [], 422);
            }

            $data = $request->validate([
                'query' => 'required|string|max:'.self::MAX_QUERY_LENGTH,
                'locale' => 'sometimes|string|in:da,en',
                'surface' => 'sometimes|string|in:home,vehicles,navbar',
            ]);

            $locale = $data['locale'] ?? app()->getLocale();
            $result = $this->aiSearchParseService->parse(
                query: $data['query'],
                locale: $locale,
            );

            $this->searchQueryLogService->log(
                surface: $data['surface'] ?? 'home',
                query: $data['query'],
                locale: $locale,
                filters: $result['filters'] ?? null,
            );

            return $this->success([
                'filters' => $result['filters'],
                'labels' => $result['labels'],
                'query' => $result['query'],
                'expanded_query' => $result['expanded_query'],
                'provider' => $result['provider'],
                'cached' => $result['cached'],
                'fallback' => $result['fallback'],
                'ai_search' => 1,
            ]);
        } catch (AiGenerationException $e) {
            return $this->error($e->getMessage(), [], $e->statusCode());
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }

    /**
     * GET /api/v1/search/suggest — autocomplete (no AI).
     */
    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => 'sometimes|string|max:120',
            'locale' => 'sometimes|string|in:da,en',
            'limit' => 'sometimes|integer|min:1|max:12',
        ]);

        $payload = $this->aiSearchParseService->suggest(
            term: $data['q'] ?? '',
            locale: $data['locale'] ?? app()->getLocale(),
            limit: (int) ($data['limit'] ?? 6),
        );

        return $this->success($payload);
    }

    /**
     * GET /api/v1/search/examples — curated NL example queries.
     */
    public function examples(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => 'sometimes|string|in:da,en',
        ]);

        return $this->success([
            'examples' => $this->suggestionService->exampleQueries($data['locale'] ?? app()->getLocale()),
        ]);
    }

    /**
     * POST /saved-searches — cookie-auth marketplace users (web).
     */
    public function saveSearchWeb(Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request);
        if (! $user) {
            return $this->error(__('messages.api.unauthorized_access'), [], 401);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
        ]);

        $search = SavedSearch::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'filters' => $data['filters'],
            'created_at' => now(),
        ]);

        return $this->created($search);
    }

    /**
     * POST /api/v1/saved-searches — Bearer token marketplace users.
     */
    public function saveSearch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
        ]);

        $search = SavedSearch::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'filters' => $data['filters'],
            'created_at' => now(),
        ]);

        return $this->created($search);
    }
}