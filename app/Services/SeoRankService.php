<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Collection;
use Exception;

class SeoRankService
{
    private const API_URL = 'https://api.seo-rank.com';
    private const CACHE_TTL = 2592000; // 30 days
    private const BATCH_SIZE = 100;
    private const RATE_LIMIT_KEY = 'seorank_api_calls';

    public function fetchMetrics(string $domain): array
    {
        $cacheKey = "seorank:metrics:" . md5($domain);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            return $this->queryApi($domain);
        });
    }

    public function fetchBatch(array $domains): Collection
    {
        $results = collect();
        $apiKey = config('services.seorank.key');

        if (!$apiKey) {
            throw new Exception('SeoRank API key not configured');
        }

        foreach (array_chunk($domains, self::BATCH_SIZE) as $chunk) {
            foreach ($chunk as $domain) {
                if ($this->checkRateLimit()) {
                    $results->push($this->fetchMetrics($domain));
                }
            }
        }

        return $results;
    }

    private function queryApi(string $domain): array
    {
        $apiKey = config('services.seorank.key');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])
            ->timeout(30)
            ->get(self::API_URL . "/domain/{$domain}");

            if (!$response->successful()) {
                throw new Exception("API returned status: " . $response->status());
            }

            return $this->parseResponse($domain, $response->json());
        } catch (Exception $e) {
            throw new Exception("SeoRank API error: " . $e->getMessage());
        }
    }

    private function parseResponse(string $domain, array $data): array
    {
        return [
            'domain' => $domain,
            'da' => $data['metrics']['da'] ?? 0,
            'pa' => $data['metrics']['pa'] ?? 0,
            'alexa_rank' => $data['metrics']['alexa_rank'] ?? null,
            'semrush_rank' => $data['metrics']['semrush_rank'] ?? null,
            'backlinks_count' => $data['metrics']['backlinks'] ?? 0,
        ];
    }

    private function checkRateLimit(): bool
    {
        $limit = config('services.seorank.rate_limit', 1000);
        
        return RateLimiter::attempt(
            self::RATE_LIMIT_KEY,
            $limit,
            function () {
                return true;
            },
            86400 // 1 day
        );
    }
}
