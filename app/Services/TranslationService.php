<?php

namespace App\Services;

use App\Models\TranslationKey;
use App\Models\TranslationValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TranslationService
{
    private const CACHE_PREFIX = 'translations';
    private const CACHE_TTL = 86400; // 24 hours
    private const DEFAULT_LOCALE = 'da';
    private const SUPPORTED_LOCALES = ['en', 'da'];

    /**
     * Get translation for a key and locale
     */
    public function get(string $key, ?string $locale = null, array $replace = []): string
    {
        $locale = $locale ?? app()->getLocale() ?? self::DEFAULT_LOCALE;

        // Get from cache or database
        $translation = $this->getCachedTranslation($key, $locale);

        // Replace placeholders
        if (!empty($replace)) {
            foreach ($replace as $search => $value) {
                $translation = str_replace(':' . $search, $value, $translation);
                $translation = str_replace('{' . $search . '}', $value, $translation);
            }
        }

        return $translation;
    }

    /**
     * Get multiple translations efficiently
     */
    public function getBatch(array $keys, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale() ?? self::DEFAULT_LOCALE;
        $result = [];

        // Load all translations for this locale from cache
        $allTranslations = $this->getAllCachedTranslations($locale);

        foreach ($keys as $key) {
            $result[$key] = $allTranslations[$key] ?? $this->getDefaultValue($key);
        }

        return $result;
    }

    /**
     * Get cached translation
     */
    private function getCachedTranslation(string $key, string $locale): string
    {
        $cacheKey = $this->getCacheKey($key, $locale);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $locale) {
            $translationKey = TranslationKey::where('key', $key)->first();
            
            if (!$translationKey) {
                return $key; // Return key if not found
            }

            // Try to get translation for locale
            $translationValue = $translationKey->values()->where('locale', $locale)->first();
            
            if ($translationValue) {
                return $translationValue->value;
            }

            // Fallback to default locale if not found
            if ($locale !== self::DEFAULT_LOCALE) {
                $defaultValue = $translationKey->values()->where('locale', self::DEFAULT_LOCALE)->first();
                if ($defaultValue) {
                    return $defaultValue->value;
                }
            }

            // Fallback to default_value from translation_key
            return $translationKey->default_value;
        });
    }

    /**
     * Get all cached translations for a locale
     */
    private function getAllCachedTranslations(string $locale): array
    {
        $cacheKey = $this->getAllCacheKey($locale);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            $translations = [];
            
            $translationKeys = TranslationKey::with(['values' => function ($query) use ($locale) {
                $query->where('locale', $locale);
            }])->get();

            foreach ($translationKeys as $key) {
                $value = $key->values->first();
                if ($value) {
                    $translations[$key->key] = $value->value;
                } else {
                    // Fallback to default_value
                    $translations[$key->key] = $key->default_value;
                }
            }

            return $translations;
        });
    }

    /**
     * Get default value for a key
     */
    private function getDefaultValue(string $key): string
    {
        $translationKey = TranslationKey::where('key', $key)->first();
        return $translationKey ? $translationKey->default_value : $key;
    }

    /**
     * Get cache key for a single translation
     */
    private function getCacheKey(string $key, string $locale): string
    {
        return self::CACHE_PREFIX . ":{$locale}:{$key}";
    }

    /**
     * Get cache key for all translations of a locale
     */
    private function getAllCacheKey(string $locale): string
    {
        return self::CACHE_PREFIX . ":{$locale}:all";
    }

    /**
     * Invalidate cache for a translation key
     */
    public function invalidateCache(?string $key = null, ?string $locale = null): void
    {
        if ($key && $locale) {
            Cache::forget($this->getCacheKey($key, $locale));
        } elseif ($key) {
            // Invalidate for all locales
            foreach (self::SUPPORTED_LOCALES as $loc) {
                Cache::forget($this->getCacheKey($key, $loc));
            }
        } else {
            // Invalidate all
            foreach (self::SUPPORTED_LOCALES as $loc) {
                Cache::forget($this->getAllCacheKey($loc));
            }
        }
    }

    /**
     * Create or update a translation key
     */
    public function createOrUpdateKey(string $key, string $defaultValue): TranslationKey
    {
        $translationKey = TranslationKey::updateOrCreate(
            ['key' => $key],
            ['default_value' => $defaultValue]
        );

        $this->invalidateCache($key);
        return $translationKey;
    }

    /**
     * Create or update a translation value
     */
    public function createOrUpdateValue(int $translationKeyId, string $locale, string $value): TranslationValue
    {
        $translationValue = TranslationValue::updateOrCreate(
            [
                'translation_key_id' => $translationKeyId,
                'locale' => $locale,
            ],
            ['value' => $value]
        );

        $translationKey = TranslationKey::find($translationKeyId);
        if ($translationKey) {
            $this->invalidateCache($translationKey->key, $locale);
        }

        return $translationValue;
    }

    /**
     * Delete a translation key
     */
    public function deleteKey(int $id): bool
    {
        $translationKey = TranslationKey::find($id);
        if ($translationKey) {
            $this->invalidateCache($translationKey->key);
            return $translationKey->delete();
        }
        return false;
    }

    /**
     * Import translations from file (Excel/CSV)
     */
    public function importFromFile(string $filePath, string $fileType): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        try {
            $data = $this->parseFile($filePath, $fileType);
            
            DB::beginTransaction();

            foreach ($data as $row) {
                try {
                    // Validate required columns
                    if (empty($row['key']) || empty($row['default_value'])) {
                        $results['errors'][] = "Row missing key or default_value: " . json_encode($row);
                        continue;
                    }

                    // Create or update translation key
                    $translationKey = $this->createOrUpdateKey(
                        $row['key'],
                        $row['default_value']
                    );

                    // Update English translation if provided
                    if (isset($row['en']) && !empty($row['en'])) {
                        $this->createOrUpdateValue($translationKey->id, 'en', $row['en']);
                        $results['updated']++;
                    } else {
                        // Use default_value as English if not provided
                        $this->createOrUpdateValue($translationKey->id, 'en', $row['default_value']);
                        $results['updated']++;
                    }

                    // Update Danish translation if provided
                    if (isset($row['da']) && !empty($row['da'])) {
                        $this->createOrUpdateValue($translationKey->id, 'da', $row['da']);
                        $results['updated']++;
                    }

                    $results['created']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Error processing row: " . $e->getMessage();
                    Log::error('Translation import error', [
                        'row' => $row,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
            $this->invalidateCache(); // Invalidate all cache after import

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Import failed: " . $e->getMessage();
            Log::error('Translation import failed', [
                'error' => $e->getMessage(),
                'file' => $filePath,
            ]);
        }

        return $results;
    }

    /**
     * Parse file (Excel or CSV)
     */
    private function parseFile(string $filePath, string $fileType): array
    {
        $data = [];

        if (in_array($fileType, ['xlsx', 'xls'])) {
            // Use Laravel Excel if available, otherwise use PhpSpreadsheet
            if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
                $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $filePath)[0];
            } else {
                // Fallback to PhpSpreadsheet
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                    $fileType === 'xlsx' ? 'Xlsx' : 'Xls'
                );
                $spreadsheet = $reader->load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray();
            }

            // Remove header row
            $headers = array_shift($data);
            
            // Map data to associative array
            $mappedData = [];
            foreach ($data as $row) {
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }
                $mappedRow = [];
                foreach ($headers as $index => $header) {
                    $mappedRow[strtolower(trim($header))] = $row[$index] ?? '';
                }
                $mappedData[] = $mappedRow;
            }
            return $mappedData;
        } elseif ($fileType === 'csv') {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                throw new \Exception('Could not open CSV file');
            }

            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                throw new \Exception('CSV file is empty or invalid');
            }

            // Normalize headers
            $headers = array_map(function ($header) {
                return strtolower(trim($header));
            }, $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }
                $mappedRow = [];
                foreach ($headers as $index => $header) {
                    $mappedRow[$header] = $row[$index] ?? '';
                }
                $data[] = $mappedRow;
            }

            fclose($handle);
            return $data;
        } else {
            throw new \Exception("Unsupported file type: {$fileType}");
        }
    }

    /**
     * Export translations to array format
     */
    public function exportToArray(?string $locale = null): array
    {
        $translationKeys = TranslationKey::with('values')->get();
        $data = [];

        foreach ($translationKeys as $key) {
            $row = [
                'key' => $key->key,
                'default_value' => $key->default_value,
            ];

            foreach (self::SUPPORTED_LOCALES as $loc) {
                $value = $key->values()->where('locale', $loc)->first();
                $row[$loc] = $value ? $value->value : '';
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get supported locales
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }
}
