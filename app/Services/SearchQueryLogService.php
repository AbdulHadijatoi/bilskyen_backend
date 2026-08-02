<?php

namespace App\Services;

use App\Models\SearchQueryLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Persist user-submitted search / advisor queries for later mining.
 * Never called from autocomplete keystrokes.
 */
class SearchQueryLogService
{
    public const MAX_QUERY_LENGTH = 200;

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public function log(string $surface, string $query, string $locale = 'da', ?array $filters = null, ?int $userId = null): void
    {
        $query = trim($query);
        if ($query === '') {
            return;
        }

        if (mb_strlen($query) > self::MAX_QUERY_LENGTH) {
            $query = mb_substr($query, 0, self::MAX_QUERY_LENGTH);
        }

        $locale = $locale === 'en' ? 'en' : 'da';
        $surface = preg_replace('/[^a-z_]/', '', strtolower($surface)) ?: 'home';

        try {
            SearchQueryLog::query()->create([
                'locale' => $locale,
                'surface' => $surface,
                'query' => $query,
                'user_id' => $userId ?? Auth::id(),
                'filters' => $filters,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('search_query_log_failed', ['error' => $e->getMessage()]);
        }
    }
}
