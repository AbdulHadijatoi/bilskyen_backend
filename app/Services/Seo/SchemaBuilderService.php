<?php

namespace App\Services\Seo;

class SchemaBuilderService
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $type, array $fields): array
    {
        return match ($type) {
            'LocalBusiness' => $this->localBusiness($fields),
            'Organization' => $this->organization($fields),
            'WebSite' => $this->webSite($fields),
            'Vehicle' => $this->vehicle($fields),
            'FAQPage' => $this->faqPage($fields),
            'BreadcrumbList' => $this->breadcrumbList($fields),
            default => throw new \InvalidArgumentException("Unknown schema type: {$type}"),
        };
    }

    /**
     * @return list<array{value: string, label: string, fields: list<string>}>
     */
    public function presets(): array
    {
        return [
            ['value' => 'LocalBusiness', 'label' => 'Local Business', 'fields' => ['name', 'url', 'telephone', 'address', 'image']],
            ['value' => 'Organization', 'label' => 'Organization', 'fields' => ['name', 'url', 'logo', 'description']],
            ['value' => 'WebSite', 'label' => 'Web Site', 'fields' => ['name', 'url', 'description']],
            ['value' => 'Vehicle', 'label' => 'Vehicle', 'fields' => ['name', 'brand', 'model', 'year', 'price', 'mileage', 'fuel', 'url', 'image']],
            ['value' => 'FAQPage', 'label' => 'FAQ Page', 'fields' => ['faqs']],
            ['value' => 'BreadcrumbList', 'label' => 'Breadcrumb List', 'fields' => ['items']],
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function localBusiness(array $fields): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $fields['name'] ?? null,
            'url' => $fields['url'] ?? null,
            'telephone' => $fields['telephone'] ?? null,
            'image' => $fields['image'] ?? null,
            'address' => ! empty($fields['address']) ? ['@type' => 'PostalAddress', 'streetAddress' => $fields['address']] : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function organization(array $fields): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $fields['name'] ?? null,
            'url' => $fields['url'] ?? null,
            'logo' => $fields['logo'] ?? null,
            'description' => $fields['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function webSite(array $fields): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $fields['name'] ?? null,
            'url' => $fields['url'] ?? null,
            'description' => $fields['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function vehicle(array $fields): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Vehicle',
            'name' => $fields['name'] ?? null,
            'brand' => ! empty($fields['brand']) ? ['@type' => 'Brand', 'name' => $fields['brand']] : null,
            'model' => $fields['model'] ?? null,
            'vehicleModelDate' => $fields['year'] ?? null,
            'url' => $fields['url'] ?? null,
            'image' => $fields['image'] ?? null,
            'fuelType' => $fields['fuel'] ?? null,
            'mileageFromOdometer' => ! empty($fields['mileage']) ? [
                '@type' => 'QuantitativeValue',
                'value' => (int) $fields['mileage'],
                'unitCode' => 'KMT',
            ] : null,
        ];

        if (! empty($fields['price'])) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => (float) $fields['price'],
                'priceCurrency' => 'DKK',
            ];
        }

        return array_filter($schema);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function faqPage(array $fields): array
    {
        $faqs = $fields['faqs'] ?? [];
        if (is_string($faqs)) {
            $faqs = json_decode($faqs, true) ?: [];
        }

        $entities = [];
        foreach ($faqs as $item) {
            if (empty($item['question']) || empty($item['answer'])) {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function breadcrumbList(array $fields): array
    {
        $items = $fields['items'] ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        $list = [];
        $position = 1;
        foreach ($items as $item) {
            if (empty($item['name']) || empty($item['url'])) {
                continue;
            }
            $list[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }
}
