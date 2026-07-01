<?php

namespace App\Http\Controllers;

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use App\Models\AiUsageLog;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAiController extends Controller
{
    public function __construct(
        private AiService $aiService,
    ) {}

    public function usage(Request $request): JsonResponse
    {
        $query = AiUsageLog::query()
            ->with([
                'user:id,name,email',
                'dealer:id,user_id,slug,cvr',
                'dealer.owner:id,name,email',
            ])
            ->orderByDesc('id');

        if ($request->filled('dealer_id')) {
            $query->where('dealer_id', $request->integer('dealer_id'));
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        if ($request->filled('task')) {
            $query->where('task', $request->input('task'));
        }

        return $this->paginated($query->paginate($request->integer('limit', 30)));
    }

    public function promptTemplates(): JsonResponse
    {
        $templates = AiPromptTemplate::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success($templates);
    }

    public function updatePromptTemplate(Request $request, int $id): JsonResponse
    {
        $template = AiPromptTemplate::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:500',
            'system_prompt' => 'sometimes|string|max:20000',
            'user_prompt_template' => 'sometimes|string|max:20000',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
        ]);

        $template->update($data);

        return $this->success($template->fresh());
    }

    public function testProvider(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', Rule::in(['openai', 'anthropic', 'gemini'])],
        ]);

        $result = $this->aiService->testProvider($data['provider']);

        if (! $result['success']) {
            return $this->error($result['message'], [], 422);
        }

        return $this->success($result);
    }
}
