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
            'AutoDealer' => $this->autoDealer($fields),
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
     * Sitewide Organization + WebSite graph (no SearchAction).
     *
     * @return array<string, mixed>
     */
    public function sitewideGraph(): array
    {
        $company = \App\Support\CompanyProfile::class;

        $org = $this->organization([
            'name' => $company::name(),
            'legalName' => $company::legalName(),
            'url' => url('/'),
            'logo' => $company::logoUrl(),
            'email' => $company::email(),
            'telephone' => $company::publicPhone(),
            'taxID' => $company::cvr(),
            'street' => $company::street(),
            'postalCode' => $company::postalCode(),
            'addressLocality' => $company::city(),
            'addressCountry' => $company::country(),
            'sameAs' => $company::sameAs(),
        ]);
        unset($org['@context']);

        $web = $this->webSite([
            'name' => $company::name(),
            'url' => url('/'),
        ]);
        unset($web['@context']);

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([$org, $web])),
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function organization(array $fields): array
    {
        $sameAs = $fields['sameAs'] ?? [];
        if (! is_array($sameAs)) {
            $sameAs = [];
        }
        $sameAs = array_values(array_filter($sameAs, fn ($url) => is_string($url) && str_starts_with(strtolower($url), 'https://')));

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $fields['street'] ?? ($fields['address'] ?? null),
            'postalCode' => $fields['postalCode'] ?? null,
            'addressLocality' => $fields['addressLocality'] ?? null,
            'addressCountry' => $fields['addressCountry'] ?? null,
        ]);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $fields['name'] ?? null,
            'legalName' => $fields['legalName'] ?? null,
            'url' => $fields['url'] ?? null,
            'logo' => $fields['logo'] ?? null,
            'description' => $fields['description'] ?? null,
            'email' => $fields['email'] ?? null,
            'telephone' => $fields['telephone'] ?? null,
            'taxID' => $fields['taxID'] ?? null,
            'address' => count($address) > 1 ? $address : null,
            'sameAs' => $sameAs !== [] ? $sameAs : null,
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
            $offer = array_filter([
                '@type' => 'Offer',
                'price' => (float) $fields['price'],
                'priceCurrency' => 'DKK',
                'url' => $fields['url'] ?? null,
                'availability' => $fields['availability'] ?? null,
                'itemCondition' => $fields['itemCondition'] ?? 'https://schema.org/UsedCondition',
                'seller' => $fields['seller'] ?? null,
            ]);
            $schema['offers'] = $offer;
        }

        return array_filter($schema);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function autoDealer(array $fields): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'AutoDealer',
            '@id' => $fields['id'] ?? null,
            'name' => $fields['name'] ?? null,
            'url' => $fields['url'] ?? null,
            'image' => $fields['image'] ?? null,
            'telephone' => $fields['telephone'] ?? null,
            'address' => ! empty($fields['address']) ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $fields['address'],
            ] : null,
        ]);
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
