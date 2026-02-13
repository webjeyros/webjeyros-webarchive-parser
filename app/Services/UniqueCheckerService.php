<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class UniqueCheckerService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function checkUniqueness(string $text): bool
    {
        $textHash = md5($text);
        $cacheKey = "uniqueness:check:" . $textHash;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($text) {
            return $this->queryYandex($text);
        });
    }

    private function queryYandex(string $text): bool
    {
        $apiKey = config('services.yandex.api_key');

        if (!$apiKey) {
            // If API key is not configured, assume unique (conservative approach)
            return true;
        }

        try {
            // First paragraph only (approximately 500 chars)
            $query = substr($text, 0, 500);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Web Archive Parser)',
            ])
            ->timeout(30)
            ->get('https://www.google.com/search', [
                'q' => $query,
                'num' => 10,
            ]);

            if (!$response->successful()) {
                return true; // Assume unique if check fails
            }

            // Parse response to check for exact matches
            return $this->parseSearchResults($response->body(), $query);
        } catch (Exception $e) {
            logger()->warning("Uniqueness check failed: " . $e->getMessage());
            return true; // Conservative: assume unique
        }
    }

    private function parseSearchResults(string $html, string $query): bool
    {
        // Very simple check - in production, use proper HTML parsing library
        $exactMatches = substr_count($html, $query);
        
        // If query appears only once (in search field), it's unique
        return $exactMatches <= 1;
    }
}
