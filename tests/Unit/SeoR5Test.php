<?php

namespace Tests\Unit;

use App\Models\SeoRedirect;
use App\Services\Seo\SchemaBuilderService;
use Tests\TestCase;

class SeoR5Test extends TestCase
{
    public function test_redirect_path_normalization(): void
    {
        $this->assertSame('/old-page', SeoRedirect::normalizePath('old-page'));
        $this->assertSame('/', SeoRedirect::normalizePath('/'));
    }

    public function test_schema_builder_local_business(): void
    {
        $service = new SchemaBuilderService;
        $json = $service->build('LocalBusiness', [
            'name' => 'Bilskyen',
            'url' => 'https://example.test',
        ]);

        $this->assertSame('LocalBusiness', $json['@type']);
        $this->assertSame('Bilskyen', $json['name']);
    }

    public function test_schema_builder_faq_page(): void
    {
        $service = new SchemaBuilderService;
        $json = $service->build('FAQPage', [
            'faqs' => [
                ['question' => 'Q1', 'answer' => 'A1'],
            ],
        ]);

        $this->assertSame('FAQPage', $json['@type']);
        $this->assertCount(1, $json['mainEntity']);
    }
}
