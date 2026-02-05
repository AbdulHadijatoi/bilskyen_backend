<?php

namespace App\Console\Commands;

use App\Models\TranslationKey;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExtractTranslations extends Command
{
    protected $signature = 'translations:extract 
                            {--path= : Specific path to scan}
                            {--dry-run : Show what would be extracted without saving}
                            {--force : Force update existing keys}';

    protected $description = 'Extract hardcoded strings from Laravel and Vue files';

    private TranslationService $translationService;
    private array $extracted = [];
    private array $patterns = [
        // Vue template strings
        'vue_template' => [
            '/>([^<>{}\[\]]{3,})</', // Text between tags
            '/label="([^"]{3,})"/', // Label attributes
            '/title="([^"]{3,})"/', // Title attributes
            '/placeholder="([^"]{3,})"/', // Placeholder attributes
            '/hint="([^"]{3,})"/', // Hint attributes
        ],
        // Vue script strings
        'vue_script' => [
            '/["\']([^"\']{3,})["\']/', // String literals
            '/`([^`]{3,})`/', // Template literals
        ],
        // PHP strings
        'php' => [
            '/["\']([^"\']{3,})["\']/', // String literals
            '/trans\(["\']([^"\']+)["\']/', // Already using trans()
            '/__\(["\']([^"\']+)["\']/', // Already using __()
        ],
    ];

    public function __construct(TranslationService $translationService)
    {
        parent::__construct();
        $this->translationService = $translationService;
    }

    public function handle(): int
    {
        $this->info('Starting translation extraction...');

        $path = $this->option('path');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($path) {
            $this->scanPath($path);
        } else {
            // Get the workspace root (one level up from backend)
            $workspaceRoot = dirname(base_path());
            
            // Scan Laravel files
            $this->info('Scanning Laravel files...');
            $this->scanPath(app_path('Http/Controllers'));
            $this->scanPath(app_path('Http/Requests'));
            $this->scanPath(app_path('Services'));
            $this->scanPath(resource_path('views'));

            // Scan Vue files
            $this->info('Scanning Vue files...');
            $this->scanPath($workspaceRoot . '/panel_vue/src/views');
            $this->scanPath($workspaceRoot . '/panel_vue/src/components');
        }

        $this->info("\nExtracted " . count($this->extracted) . " unique strings");

        if ($dryRun) {
            $this->displayExtracted();
            return Command::SUCCESS;
        }

        // Save to database
        $this->info('Saving to database...');
        $saved = 0;
        $skipped = 0;

        foreach ($this->extracted as $key => $defaultValue) {
            $translationKey = TranslationKey::where('key', $key)->first();

            if ($translationKey && !$force) {
                $skipped++;
                continue;
            }

            $this->translationService->createOrUpdateKey($key, $defaultValue);
            $saved++;
        }

        $this->info("Saved: {$saved}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }

    private function scanPath(string $path): void
    {
        if (!File::exists($path)) {
            $this->warn("Path does not exist: {$path}");
            return;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            $extension = $file->getExtension();
            
            if ($extension === 'php') {
                $this->scanPhpFile($file->getPathname());
            } elseif ($extension === 'vue') {
                $this->scanVueFile($file->getPathname());
            }
        }
    }

    private function scanPhpFile(string $filePath): void
    {
        $content = File::get($filePath);
        
        // Skip if already using trans() or __()
        if (preg_match('/trans\(|__\(/', $content)) {
            return;
        }

        // Extract strings from PHP files
        $this->extractStrings($content, 'php', $filePath);
    }

    private function scanVueFile(string $filePath): void
    {
        $content = File::get($filePath);
        
        // Extract from template section
        if (preg_match('/<template>(.*?)<\/template>/s', $content, $matches)) {
            $this->extractStrings($matches[1], 'vue_template', $filePath);
        }

        // Extract from script section
        if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $content, $matches)) {
            $this->extractStrings($matches[1], 'vue_script', $filePath);
        }
    }

    private function extractStrings(string $content, string $type, string $filePath): void
    {
        $patterns = $this->patterns[$type] ?? [];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] ?? [] as $match) {
                    $string = trim($match);
                    
                    // Skip if too short, contains variables, or is a URL/path
                    if (strlen($string) < 3) {
                        continue;
                    }
                    
                    if ($this->shouldSkip($string)) {
                        continue;
                    }

                    $key = $this->generateKey($string, $filePath);
                    $this->extracted[$key] = $string;
                }
            }
        }
    }

    private function shouldSkip(string $string): bool
    {
        // Skip URLs, paths, email addresses, etc.
        if (preg_match('/^(https?:\/\/|mailto:|\.|\/|\\\\)/', $string)) {
            return true;
        }

        // Skip if contains variables or expressions
        if (preg_match('/\{\{|\$\{|%[sd]|:[\w]+/', $string)) {
            return true;
        }

        // Skip single words that are likely code
        if (preg_match('/^[A-Z_][A-Z0-9_]*$/', $string)) {
            return true;
        }

        // Skip if it's just numbers or special characters
        if (preg_match('/^[0-9\s\-_\.]+$/', $string)) {
            return true;
        }

        return false;
    }

    private function generateKey(string $string, string $filePath): string
    {
        // Generate a key from the string and file path
        $baseName = basename($filePath, '.php');
        $baseName = basename($baseName, '.vue');
        
        // Normalize the string to create a key
        $key = Str::slug($string, '_');
        $key = preg_replace('/[^a-z0-9_]/', '', strtolower($key));
        
        // Limit key length
        $key = substr($key, 0, 100);
        
        // Add context from filename if key is too generic
        if (strlen($key) < 10) {
            $context = Str::slug($baseName, '_');
            $key = $context . '_' . $key;
        }

        return $key;
    }

    private function displayExtracted(): void
    {
        $this->table(
            ['Key', 'Default Value'],
            array_map(
                fn($key, $value) => [$key, substr($value, 0, 50)],
                array_keys($this->extracted),
                array_values($this->extracted)
            )
        );
    }
}
