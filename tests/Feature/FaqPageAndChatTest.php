<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Services\AiService;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\FaqContentService;
use App\Services\Finance\FinanceCalculatorService;
use App\Services\LookupService;
use App\Services\MarketPricingService;
use App\Services\Marketing\MetaConversionsApiService;
use App\Services\MailService;
use App\Services\PageContentService;
use App\Services\PlatformSettingService;
use App\Services\SeoService;
use App\Services\VehicleDetailPresentationService;
use App\Services\VehicleListingPresentationService;
use App\Services\VehicleService;
use App\Services\VehicleTrustReportService;
use App\Services\VehicleViewService;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class FaqPageAndChatTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeHomeController(
        PlatformSettingService $settings,
        FaqContentService $faq,
        ?SeoService $seo = null,
    ): HomeController {
        return new HomeController(
            Mockery::mock(AuthService::class),
            Mockery::mock(VehicleService::class),
            Mockery::mock(AuditLogService::class),
            Mockery::mock(PageContentService::class),
            Mockery::mock(LookupService::class),
            $seo ?? tap(Mockery::mock(SeoService::class), function ($mock) {
                $mock->shouldReceive('getForPage')->andReturn([]);
            }),
            Mockery::mock(VehicleDetailPresentationService::class),
            Mockery::mock(VehicleViewService::class),
            Mockery::mock(MailService::class),
            Mockery::mock(FinanceCalculatorService::class),
            Mockery::mock(VehicleTrustReportService::class),
            Mockery::mock(MarketPricingService::class),
            Mockery::mock(VehicleListingPresentationService::class),
            Mockery::mock(MetaConversionsApiService::class),
            $faq,
            $settings,
        );
    }

    public function test_faq_page_aborts_when_disabled(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('isFaqPageEnabled')->andReturn(false);

        $faq = Mockery::mock(FaqContentService::class);
        $controller = $this->makeHomeController($settings, $faq);

        $this->expectException(NotFoundHttpException::class);
        $controller->showFaq();
    }

    public function test_faq_page_view_when_enabled_without_chatbot(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('isFaqPageEnabled')->andReturn(true);
        $settings->shouldReceive('isFaqChatbotEnabled')->andReturn(false);

        $faq = Mockery::mock(FaqContentService::class);
        $faq->shouldReceive('getPublicContent')->andReturn([
            'header_title' => 'Help & FAQ',
            'header_description' => 'Answers',
            'sections' => [
                [
                    'id' => 's1',
                    'title' => 'Buying',
                    'order' => 0,
                    'items' => [
                        [
                            'id' => 'q1',
                            'question' => 'How do I browse?',
                            'answer' => 'Open Vehicles.',
                            'order' => 0,
                        ],
                    ],
                ],
            ],
        ]);
        $faq->shouldReceive('flattenQaPairs')->andReturn([
            ['question' => 'How do I browse?', 'answer' => 'Open Vehicles.'],
        ]);

        $controller = $this->makeHomeController($settings, $faq);
        $view = $controller->showFaq();

        $this->assertSame('faq', $view->name());
        $this->assertSame('Help & FAQ', $view->getData()['faqHeaderTitle']);
        $this->assertFalse($view->getData()['faqChatbotEnabled']);
        $this->assertNotNull($view->getData()['faqSchema']);
    }

    public function test_faq_chat_forbidden_when_chatbot_disabled(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('isFaqPageEnabled')->andReturn(true);
        $settings->shouldReceive('isFaqChatbotEnabled')->andReturn(false);
        $this->app->instance(PlatformSettingService::class, $settings);

        $this->postJson('/api/v1/faq/chat', [
            'message' => 'How do I buy a car?',
            'website' => '',
        ])->assertForbidden();
    }

    public function test_faq_chat_uses_ai_when_enabled(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('isFaqPageEnabled')->andReturn(true);
        $settings->shouldReceive('isFaqChatbotEnabled')->andReturn(true);
        $this->app->instance(PlatformSettingService::class, $settings);

        $faq = Mockery::mock(FaqContentService::class);
        $faq->shouldReceive('buildKnowledgeBaseText')->andReturn("## Buying\nQ: How?\nA: Like this.");
        $this->app->instance(FaqContentService::class, $faq);

        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('generateFaqChat')
            ->once()
            ->andReturn([
                'text' => 'Browse the Vehicles page.',
                'provider' => 'openai',
                'model' => 'gpt-test',
                'task' => 'faq_chat',
                'tokens' => 10,
            ]);
        $this->app->instance(AiService::class, $ai);

        $response = $this->postJson('/api/v1/faq/chat', [
            'message' => 'How do I buy a car?',
            'locale' => 'en',
            'website' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.reply', 'Browse the Vehicles page.');
    }

    public function test_faq_chat_rejects_empty_knowledge(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('isFaqPageEnabled')->andReturn(true);
        $settings->shouldReceive('isFaqChatbotEnabled')->andReturn(true);
        $this->app->instance(PlatformSettingService::class, $settings);

        $faq = Mockery::mock(FaqContentService::class);
        $faq->shouldReceive('buildKnowledgeBaseText')->andReturn('');
        $this->app->instance(FaqContentService::class, $faq);

        $this->postJson('/api/v1/faq/chat', [
            'message' => 'Hello',
            'website' => '',
        ])->assertStatus(422);
    }
}
