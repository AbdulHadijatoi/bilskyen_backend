<?php

namespace App\Http\Controllers;

use App\Exceptions\AiGenerationException;
use App\Services\AiService;
use App\Services\FaqContentService;
use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FaqChatController extends Controller
{
    private const MAX_MESSAGE_LENGTH = 2000;

    private const MAX_HISTORY_TURNS = 10;

    private const MAX_HISTORY_MESSAGE_LENGTH = 1000;

    public function __construct(
        private PlatformSettingService $platformSettingService,
        private FaqContentService $faqContentService,
        private AiService $aiService,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        try {
            if (! $this->platformSettingService->isFaqPageEnabled()
                || ! $this->platformSettingService->isFaqChatbotEnabled()) {
                return $this->error(__('messages.api.faq_chatbot_disabled'), [], 403);
            }

            $data = $request->validate([
                'message' => 'required|string|max:'.self::MAX_MESSAGE_LENGTH,
                'locale' => 'sometimes|string|in:da,en',
                'history' => 'sometimes|array|max:'.self::MAX_HISTORY_TURNS,
                'history.*.role' => 'required_with:history|string|in:user,assistant',
                'history.*.content' => 'required_with:history|string|max:'.self::MAX_HISTORY_MESSAGE_LENGTH,
            ]);

            $knowledge = $this->faqContentService->buildKnowledgeBaseText();
            if ($knowledge === '') {
                return $this->error(__('messages.api.faq_knowledge_empty'), [], 422);
            }

            $historyLines = [];
            foreach ($data['history'] ?? [] as $turn) {
                $role = $turn['role'] === 'assistant' ? 'Assistant' : 'User';
                $historyLines[] = $role.': '.$turn['content'];
            }

            $context = [
                'knowledge_base' => $knowledge,
                'conversation_history' => $historyLines !== [] ? implode("\n", $historyLines) : '(none)',
                'user_message' => $data['message'],
            ];

            $result = $this->aiService->generateFaqChat(
                context: $context,
                locale: $data['locale'] ?? app()->getLocale(),
            );

            return $this->success([
                'reply' => $result['text'] ?? '',
                'provider' => $result['provider'] ?? null,
            ]);
        } catch (AiGenerationException $e) {
            return $this->error($e->getMessage(), [], $e->statusCode());
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }
}
