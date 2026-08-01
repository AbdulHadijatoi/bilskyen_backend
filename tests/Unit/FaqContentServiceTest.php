<?php

namespace Tests\Unit;

use App\Services\FaqContentService;
use App\Services\PageContentService;
use Mockery;
use Tests\TestCase;

class FaqContentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_parse_sections_orders_and_skips_empty_items(): void
    {
        $pageContent = Mockery::mock(PageContentService::class);
        $service = new FaqContentService($pageContent);

        $sections = $service->parseSections([
            [
                'id' => 'b',
                'title' => 'Second',
                'order' => 1,
                'items' => [
                    ['id' => 'i2', 'question' => 'Q2', 'answer' => 'A2', 'order' => 1],
                    ['id' => 'i1', 'question' => 'Q1', 'answer' => 'A1', 'order' => 0],
                    ['id' => 'empty', 'question' => '', 'answer' => '', 'order' => 2],
                ],
            ],
            [
                'id' => 'a',
                'title' => 'First',
                'order' => 0,
                'items' => [
                    ['id' => 'i0', 'question' => 'Hello?', 'answer' => 'World', 'order' => 0],
                ],
            ],
        ]);

        $this->assertCount(2, $sections);
        $this->assertSame('First', $sections[0]['title']);
        $this->assertSame('Second', $sections[1]['title']);
        $this->assertSame('Q1', $sections[1]['items'][0]['question']);
        $this->assertSame('Q2', $sections[1]['items'][1]['question']);
        $this->assertCount(2, $sections[1]['items']);
    }

    public function test_build_knowledge_base_text_includes_qa(): void
    {
        $pageContent = Mockery::mock(PageContentService::class);
        $service = new FaqContentService($pageContent);

        $text = $service->buildKnowledgeBaseText([
            [
                'id' => 'buying',
                'title' => 'Buying',
                'order' => 0,
                'items' => [
                    [
                        'id' => '1',
                        'question' => 'How do I buy?',
                        'answer' => 'Browse vehicles and contact a dealer.',
                        'order' => 0,
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('## Buying', $text);
        $this->assertStringContainsString('Q: How do I buy?', $text);
        $this->assertStringContainsString('A: Browse vehicles and contact a dealer.', $text);
    }

    public function test_get_public_content_reads_page_contents(): void
    {
        $pageContent = Mockery::mock(PageContentService::class);
        $pageContent->shouldReceive('getHomePageContent')
            ->once()
            ->with('faq')
            ->andReturn([
                'faq_header_title' => 'Help',
                'faq_header_description' => 'Desc',
                'faq_sections_json' => json_encode([
                    [
                        'id' => 's1',
                        'title' => 'General',
                        'order' => 0,
                        'items' => [
                            ['id' => 'q1', 'question' => 'What?', 'answer' => 'That.', 'order' => 0],
                        ],
                    ],
                ]),
            ]);

        $service = new FaqContentService($pageContent);
        $content = $service->getPublicContent();

        $this->assertSame('Help', $content['header_title']);
        $this->assertSame('Desc', $content['header_description']);
        $this->assertCount(1, $content['sections']);
        $this->assertSame('What?', $content['sections'][0]['items'][0]['question']);
    }
}
