<?php

namespace App\Http\Controllers;

use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminIntegrationController extends Controller
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success([
            'general' => $this->platformSettingService->getPublicGroup('general'),
            'crm' => $this->platformSettingService->getPublicGroup('crm'),
            'payment' => $this->platformSettingService->getPublicGroup('payment'),
            'ai' => $this->platformSettingService->getPublicGroup('ai'),
            'media' => $this->platformSettingService->getPublicGroup('media'),
            'syndication' => $this->platformSettingService->getPublicGroup('syndication'),
            'finance' => $this->platformSettingService->getPublicGroup('finance'),
            'marketing' => $this->platformSettingService->getPublicGroup('marketing'),
            'marketplace' => $this->platformSettingService->getPublicGroup('marketplace'),
            'reputation' => $this->platformSettingService->getPublicGroup('reputation'),
            'compliance' => $this->platformSettingService->getPublicGroup('compliance'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group' => 'required|in:general,crm,payment,ai,media,syndication,finance,marketing,marketplace,reputation,compliance',
            'settings' => 'required|array',
        ]);

        if ($data['group'] === 'general') {
            $request->validate([
                'settings.language_switcher_enabled' => 'sometimes|boolean',
                'settings.faq_page_enabled' => 'sometimes|boolean',
                'settings.faq_chatbot_enabled' => 'sometimes|boolean',
            ]);
        }

        if ($data['group'] === 'ai') {
            $settings = $request->input('settings', []);
            foreach (['openai_enabled', 'anthropic_enabled', 'gemini_enabled', 'deepseek_enabled', 'openrouter_enabled', 'opencodezen_enabled', 'ollama_enabled'] as $boolKey) {
                if (array_key_exists($boolKey, $settings)) {
                    $settings[$boolKey] = filter_var($settings[$boolKey], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $request->merge(['settings' => $settings]);

            $request->validate([
                'settings.openai_enabled' => 'sometimes|boolean',
                'settings.anthropic_enabled' => 'sometimes|boolean',
                'settings.gemini_enabled' => 'sometimes|boolean',
                'settings.deepseek_enabled' => 'sometimes|boolean',
                'settings.openrouter_enabled' => 'sometimes|boolean',
                'settings.opencodezen_enabled' => 'sometimes|boolean',
                'settings.ollama_enabled' => 'sometimes|boolean',
                'settings.openai_api_key' => 'sometimes|nullable|string|max:512',
                'settings.anthropic_api_key' => 'sometimes|nullable|string|max:512',
                'settings.gemini_api_key' => 'sometimes|nullable|string|max:512',
                'settings.deepseek_api_key' => 'sometimes|nullable|string|max:512',
                'settings.openrouter_api_key' => 'sometimes|nullable|string|max:512',
                'settings.opencodezen_api_key' => 'sometimes|nullable|string|max:512',
                'settings.ollama_api_key' => 'sometimes|nullable|string|max:512',
                'settings.openai_model' => 'sometimes|nullable|string|max:100',
                'settings.anthropic_model' => 'sometimes|nullable|string|max:100',
                'settings.gemini_model' => 'sometimes|nullable|string|max:100',
                'settings.deepseek_model' => 'sometimes|nullable|string|max:100',
                'settings.openrouter_model' => 'sometimes|nullable|string|max:150',
                'settings.opencodezen_model' => 'sometimes|nullable|string|max:150',
                'settings.ollama_model' => 'sometimes|nullable|string|max:100',
                'settings.ollama_base_url' => 'sometimes|nullable|string|max:255',
                'settings.max_tokens' => 'sometimes|integer|min:256|max:8000',
                'settings.temperature' => 'sometimes|numeric|min:0|max:1',
                'settings.monthly_token_budget' => 'sometimes|integer|min:0',
            ]);
        }

        $this->platformSettingService->setGroup(
            $data['group'],
            $request->input('settings', []),
            $request->user()?->id
        );

        $this->platformSettingService->logIntegration(
            $data['group'],
            'settings.update',
            'success',
            'Integration settings updated',
            $request->user()?->id
        );

        return $this->success([
            'group' => $data['group'],
            'settings' => $this->platformSettingService->getPublicGroup($data['group']),
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $query = \App\Models\IntegrationLog::query()->orderByDesc('id');

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        return $this->paginated($query->paginate($request->integer('limit', 20)));
    }

    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => 'required|string|max:64',
        ]);

        if ($data['provider'] === 'syndication_sftp') {
            $result = app(\App\Services\Feeds\SftpFeedUploadService::class)->testConnection();
            $this->platformSettingService->logIntegration('syndication', 'sftp.test', $result['success'] ? 'success' : 'failed', $result['message'], $request->user()?->id);

            return $result['success'] ? $this->success($result) : $this->error($result['message'], [], 422);
        }

        if ($data['provider'] === 'stripe') {
            $result = app(\App\Services\Payments\StripePaymentProvider::class)->testConnection();

            $this->platformSettingService->logIntegration(
                'stripe',
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

        if (in_array($data['provider'], ['openai', 'anthropic', 'gemini', 'deepseek', 'openrouter', 'opencodezen', 'ollama'], true)) {
            $result = app(\App\Services\AiService::class)->testProvider($data['provider']);

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

        $this->platformSettingService->logIntegration(
            $data['provider'],
            'test',
            'failed',
            'Connection test not supported for this provider',
            $request->user()?->id
        );

        return $this->error(
            __('messages.errors.integration_test_not_supported'),
            [],
            422
        );
    }
}
