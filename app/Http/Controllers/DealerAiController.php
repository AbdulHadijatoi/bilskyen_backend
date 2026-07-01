<?php

namespace App\Http\Controllers;

use App\Constants\AiGenerationTask;
use App\Services\AiService;
use App\Services\DealerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DealerAiController extends Controller
{
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
        $data = $request->validate([
            'task' => ['required', 'string', Rule::in(AiGenerationTask::values())],
            'locale' => 'sometimes|string|in:da,en',
            'context' => 'required|array',
            'context_type' => 'sometimes|string|max:64',
            'context_id' => 'sometimes|integer',
        ]);

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
    }
}
