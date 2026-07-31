<?php

namespace App\Services;

class FaqContentService
{
    public function __construct(
        private PageContentService $pageContentService,
    ) {}

    /**
     * @return array{header_title: string, header_description: string, sections: list<array<string, mixed>>}
     */
    public function getPublicContent(): array
    {
        $content = $this->pageContentService->getHomePageContent('faq');

        return [
            'header_title' => (string) ($content['faq_header_title'] ?? ''),
            'header_description' => (string) ($content['faq_header_description'] ?? ''),
            'sections' => $this->parseSections($content['faq_sections_json'] ?? null),
        ];
    }

    /**
     * @return list<array{id: string, title: string, order: int, items: list<array{id: string, question: string, answer: string, order: int}>}>
     */
    public function parseSections(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        $sections = [];
        foreach ($decoded as $section) {
            if (! is_array($section)) {
                continue;
            }

            $items = [];
            foreach ($section['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $question = trim((string) ($item['question'] ?? ''));
                $answer = trim((string) ($item['answer'] ?? ''));
                if ($question === '' && $answer === '') {
                    continue;
                }
                $items[] = [
                    'id' => (string) ($item['id'] ?? uniqid('faq_', true)),
                    'question' => $question,
                    'answer' => $answer,
                    'order' => (int) ($item['order'] ?? 0),
                ];
            }

            usort($items, fn (array $a, array $b) => $a['order'] <=> $b['order']);

            $sections[] = [
                'id' => (string) ($section['id'] ?? uniqid('section_', true)),
                'title' => trim((string) ($section['title'] ?? '')),
                'order' => (int) ($section['order'] ?? 0),
                'items' => $items,
            ];
        }

        usort($sections, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $sections;
    }

    /**
     * Flat knowledge text for the AI chatbot system context.
     */
    public function buildKnowledgeBaseText(?array $sections = null): string
    {
        $sections ??= $this->getPublicContent()['sections'];
        $blocks = [];

        foreach ($sections as $section) {
            $title = $section['title'] !== '' ? $section['title'] : 'General';
            $lines = ["## {$title}"];
            foreach ($section['items'] as $item) {
                if ($item['question'] === '') {
                    continue;
                }
                $lines[] = 'Q: '.$item['question'];
                $lines[] = 'A: '.$item['answer'];
                $lines[] = '';
            }
            $blocks[] = implode("\n", $lines);
        }

        return trim(implode("\n\n", $blocks));
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function flattenQaPairs(?array $sections = null): array
    {
        $sections ??= $this->getPublicContent()['sections'];
        $pairs = [];

        foreach ($sections as $section) {
            foreach ($section['items'] as $item) {
                if ($item['question'] === '') {
                    continue;
                }
                $pairs[] = [
                    'question' => $item['question'],
                    'answer' => $item['answer'],
                ];
            }
        }

        return $pairs;
    }
}
