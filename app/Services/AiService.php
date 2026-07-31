<?php

namespace App\Services;

use App\Constants\AiGenerationTask;
use App\Contracts\AiProviderInterface;
use App\Data\AiCompletionResult;
use App\Exceptions\AiGenerationException;
use App\Models\AiPromptTemplate;
use App\Models\AiUsageLog;
use App\Models\Dealer;
use App\Models\User;
use App\Services\Ai\AnthropicProvider;
use App\Services\Ai\GeminiProvider;
use App\Services\Ai\OpenAiProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiService
{
    /** @var list<class-string<AiProviderInterface>> */
    private array $providerClasses = [
        OpenAiProvider::class,
        AnthropicProvider::class,
        GeminiProvider::class,
    ];

    public function __construct(
        private PlatformSettingService $platformSettingService,
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    public function isGloballyEnabled(): bool
    {
        foreach ($this->providerClasses as $class) {
            if (app($class)->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function enabledProviderNames(): array
    {
        $names = [];
        foreach ($this->providerClasses as $class) {
            $provider = app($class);
            if ($provider->isEnabled()) {
                $names[] = $provider->getName();
            }
        }

        return $names;
    }

    public function dealerCanUseAi(?Dealer $dealer): bool
    {
        if (! $this->isGloballyEnabled()) {
            return false;
        }

        if (! $dealer) {
            return false;
        }

        return $this->subscriptionFeatureService->hasFeature($dealer, 'ai_assistant');
    }

    public function remainingRequestsForDealer(Dealer $dealer): ?int
    {
        $limit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'ai_monthly_requests', 0);
        if ($limit <= 0) {
            return null;
        }

        $used = $this->dealerRequestCountThisMonth($dealer);

        return max(0, $limit - $used);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     providers: list<string>,
     *     tasks: list<string>,
     *     remaining_requests: int|null,
     *     monthly_request_limit: int|null
     * }
     */
    public function dealerConfig(?Dealer $dealer): array
    {
        $enabled = $this->dealerCanUseAi($dealer);
        $limit = $dealer ? $this->subscriptionFeatureService->getFeatureLimit($dealer, 'ai_monthly_requests', 0) : 0;

        return [
            'enabled' => $enabled,
            'providers' => $enabled ? $this->enabledProviderNames() : [],
            'tasks' => $enabled ? AiGenerationTask::values() : [],
            'remaining_requests' => $dealer && $enabled ? $this->remainingRequestsForDealer($dealer) : null,
            'monthly_request_limit' => $limit > 0 ? $limit : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(
        string $task,
        array $context,
        User $user,
        ?Dealer $dealer = null,
        string $locale = 'da',
        ?string $contextType = null,
        ?int $contextId = null,
    ): array {
        if (! $dealer) {
            throw new AiGenerationException(__('messages.api.dealer_not_found'), 404);
        }

        if (! $this->dealerCanUseAi($dealer)) {
            throw new AiGenerationException(__('messages.api.ai_not_in_plan'), 403);
        }

        $this->assertTaskFeature($dealer, $task, $context, $contextType);

        return $this->runGeneration(
            task: $task,
            context: $context,
            user: $user,
            dealer: $dealer,
            locale: $locale,
            contextType: $contextType,
            contextId: $contextId,
            enforceDealerQuota: true,
        );
    }

    /**
     * Admin CMS copy rewrite — no dealer quota, global budget still applies.
     *
     * @param  array<string, mixed>  $context
     */
    public function generateForAdmin(
        string $task,
        array $context,
        User $user,
        string $locale = 'da',
        ?string $contextType = null,
        ?int $contextId = null,
    ): array {
        if (! $this->isGloballyEnabled()) {
            throw new AiGenerationException(__('messages.api.ai_not_enabled'), 422);
        }

        return $this->runGeneration(
            task: $task,
            context: $context,
            user: $user,
            dealer: null,
            locale: $locale,
            contextType: $contextType,
            contextId: $contextId,
            enforceDealerQuota: false,
        );
    }

    /**
     * Public sell-your-car listing help — no auth, no dealer quota.
     *
     * @param  array<string, mixed>  $context
     */
    public function generateForPublic(
        string $task,
        array $context,
        string $locale = 'da',
        string $contextType = 'public_listing',
    ): array {
        if (! $this->isGloballyEnabled()) {
            throw new AiGenerationException(__('messages.api.ai_not_enabled'), 422);
        }

        return $this->runGeneration(
            task: $task,
            context: $context,
            user: null,
            dealer: null,
            locale: $locale,
            contextType: $contextType,
            contextId: null,
            enforceDealerQuota: false,
        );
    }

    /**
     * Public FAQ chatbot — grounded on FAQ page content, uses active Integrations AI providers.
     *
     * @param  array<string, mixed>  $context
     */
    public function generateFaqChat(array $context, string $locale = 'da'): array
    {
        return $this->generateForPublic(
            task: AiGenerationTask::FAQ_CHAT,
            context: $context,
            locale: $locale,
            contextType: 'faq_chat',
        );
    }

    /**
     * Public AI vehicle search parse — maps natural language to listing filters.
     *
     * @param  array<string, mixed>  $context
     */
    public function generateSearchParse(array $context, string $locale = 'da'): array
    {
        return $this->generateForPublic(
            task: AiGenerationTask::SEARCH_PARSE,
            context: $context,
            locale: $locale,
            contextType: 'ai_search_parse',
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function runGeneration(
        string $task,
        array $context,
        ?User $user,
        ?Dealer $dealer,
        string $locale,
        ?string $contextType,
        ?int $contextId,
        bool $enforceDealerQuota,
    ): array {
        if (! AiGenerationTask::isValid($task)) {
            throw new AiGenerationException(__('messages.api.ai_invalid_task'), 422);
        }

        if (! $this->isGloballyEnabled()) {
            throw new AiGenerationException(__('messages.api.ai_not_enabled'), 422);
        }

        $this->assertWithinGlobalBudget();

        $template = AiPromptTemplate::query()
            ->where('key', $task)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            throw new AiGenerationException(__('messages.api.ai_prompt_not_found'), 422);
        }

        [$systemPrompt, $userPrompt] = $this->renderPrompts($template, $context, $locale);

        $runner = function () use ($user, $dealer, $task, $systemPrompt, $userPrompt, $contextType, $contextId, $enforceDealerQuota): array {
            if ($enforceDealerQuota && $dealer) {
                $this->assertWithinQuota($dealer);
            }

            $lastError = null;
            foreach ($this->providerClasses as $class) {
                /** @var AiProviderInterface $provider */
                $provider = app($class);
                if (! $provider->isEnabled()) {
                    continue;
                }

                try {
                    $result = $provider->complete($systemPrompt, $userPrompt);
                    $this->logUsage($user, $dealer, $task, $result, 'success', null, $contextType, $contextId);

                    return [
                        'text' => $result->text,
                        'provider' => $result->provider,
                        'model' => $result->model,
                        'task' => $task,
                        'tokens' => $result->totalTokens(),
                    ];
                } catch (\Throwable $e) {
                    $lastError = $e;
                    $this->logUsage(
                        $user,
                        $dealer,
                        $task,
                        new AiCompletionResult('', $provider->getName(), '', 0, 0),
                        'failed',
                        $e->getMessage(),
                        $contextType,
                        $contextId
                    );
                    Log::warning('AI provider failed, trying next', [
                        'provider' => $provider->getName(),
                        'task' => $task,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::error('All AI providers failed', [
                'task' => $task,
                'last_error' => $lastError?->getMessage(),
            ]);

            throw new AiGenerationException(__('messages.api.ai_all_providers_failed'), 503);
        };

        if ($enforceDealerQuota && $dealer) {
            return DB::transaction(function () use ($dealer, $runner): array {
                Dealer::query()->whereKey($dealer->id)->lockForUpdate()->first();

                return $runner();
            });
        }

        return $runner();
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function testProvider(string $providerName): array
    {
        foreach ($this->providerClasses as $class) {
            $provider = app($class);
            if ($provider->getName() === $providerName) {
                return $provider->testConnection();
            }
        }

        return [
            'success' => false,
            'message' => __('messages.api.ai_unknown_provider'),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function assertTaskFeature(Dealer $dealer, string $task, array $context, ?string $contextType): void
    {
        $taskFeatureMap = [
            AiGenerationTask::ENQUIRY_REPLY => 'enquiry_ai_replies',
            AiGenerationTask::LEAD_SUMMARY => 'lead_ai_summary',
            AiGenerationTask::LISTING_HEALTH_REWRITE => 'listing_health_ai_briefing',
        ];

        if (isset($taskFeatureMap[$task]) && ! $this->subscriptionFeatureService->hasFeature($dealer, $taskFeatureMap[$task])) {
            throw new AiGenerationException(__('messages.api.subscription_feature_required', [
                'feature' => $taskFeatureMap[$task],
            ]), 403);
        }

        $listingHealthTasks = [
            AiGenerationTask::VEHICLE_DESCRIPTION,
            AiGenerationTask::VEHICLE_TITLE,
            AiGenerationTask::VEHICLE_HIGHLIGHTS,
            AiGenerationTask::SEO_META,
            AiGenerationTask::LISTING_DESCRIPTION,
        ];

        if ($contextType === 'vehicle'
            && isset($context['issues'])
            && in_array($task, $listingHealthTasks, true)
            && ! $this->subscriptionFeatureService->hasFeature($dealer, 'listing_health_ai_fixes')) {
            throw new AiGenerationException(__('messages.api.subscription_feature_required', [
                'feature' => 'listing_health_ai_fixes',
            ]), 403);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{0: string, 1: string}
     */
    private function renderPrompts(AiPromptTemplate $template, array $context, string $locale): array
    {
        $contextBlock = $this->formatContext($context);
        $replacements = [
            '{{locale}}' => $locale,
            '{{context}}' => $contextBlock,
            '{{context_json}}' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ];

        $system = strtr($template->system_prompt, $replacements);
        $user = strtr($template->user_prompt_template, $replacements);

        return [$system, $user];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function formatContext(array $context): string
    {
        $lines = [];
        foreach ($context as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }
            $lines[] = ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
        }

        return implode("\n", $lines);
    }

    private function assertWithinQuota(?Dealer $dealer): void
    {
        if (! $dealer) {
            return;
        }

        $remaining = $this->remainingRequestsForDealer($dealer);
        if ($remaining !== null && $remaining <= 0) {
            throw new AiGenerationException(__('messages.api.ai_monthly_quota_exceeded'), 422);
        }
    }

    private function assertWithinGlobalBudget(): void
    {
        $cap = (int) $this->platformSettingService->get('ai', 'monthly_token_budget', 0);
        if ($cap <= 0) {
            return;
        }

        $used = AiUsageLog::query()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->where('status', 'success')
            ->selectRaw('COALESCE(SUM(prompt_tokens + completion_tokens), 0) as total')
            ->value('total');

        if ((int) $used >= $cap) {
            throw new AiGenerationException(__('messages.api.ai_global_budget_exceeded'), 422);
        }
    }

    private function dealerRequestCountThisMonth(Dealer $dealer): int
    {
        return AiUsageLog::query()
            ->where('dealer_id', $dealer->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->where('status', 'success')
            ->count();
    }

    private function logUsage(
        ?User $user,
        ?Dealer $dealer,
        string $task,
        AiCompletionResult $result,
        string $status,
        ?string $errorMessage,
        ?string $contextType,
        ?int $contextId,
    ): void {
        AiUsageLog::create([
            'user_id' => $user?->id,
            'dealer_id' => $dealer?->id,
            'provider' => $result->provider,
            'model' => $result->model,
            'task' => $task,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'status' => $status,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }
}
