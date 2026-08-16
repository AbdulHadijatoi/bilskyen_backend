<?php

namespace App\Http\Controllers;

use App\Constants\AiGenerationTask;
use App\Exceptions\AiGenerationException;
use App\Models\AiPromptTemplate;
use App\Models\AiUsageLog;
use App\Services\AiService;
use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminAiController extends Controller
{
    private const MAX_CONTEXT_BYTES = 32768;

    private const MAX_CONTEXT_KEYS = 50;

    public function __construct(
        private AiService $aiService,
        private PlatformSettingService $platformSettingService,
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

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
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
            'system_prompt' => 'sometimes|string|min:1|max:20000',
            'user_prompt_template' => 'sometimes|string|min:1|max:20000',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
        ]);

        $template->update($data);

        return $this->success($template->fresh());
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

            $encoded = json_encode($data['context']);
            if ($encoded !== false && strlen($encoded) > self::MAX_CONTEXT_BYTES) {
                throw ValidationException::withMessages([
                    'context' => [__('messages.api.ai_context_too_large')],
                ]);
            }

            $result = $this->aiService->generateForAdmin(
                task: $data['task'],
                context: $data['context'],
                user: $request->user(),
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

    public function testProvider(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', Rule::in(['openai', 'anthropic', 'gemini', 'deepseek', 'openrouter', 'opencodezen', 'ollama'])],
        ]);

        $result = $this->aiService->testProvider($data['provider']);

        $this->platformSettingService->logIntegration(
            $data['provider'],
            'test',
            $result['success'] ? 'success' : 'failed',
            $result['message'],
            $request->user()?->id
        );

        if (! $result['success']) {
            return $this->error($result['message'], [], 422);
        }

        return $this->success($result);
    }
}
