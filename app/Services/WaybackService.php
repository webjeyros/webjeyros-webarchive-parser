<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Exception;

class WaybackService
{
    private const API_URL = 'https://archive.org/wayback/available';
    private const CACHE_TTL = 86400; // 1 day
    private const PER_PAGE = 100;
    private const TIMEOUT = 30;

    public function searchByKeyword(string $keyword): Collection
    {
        $cacheKey = "wayback_search:" . md5($keyword);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($keyword) {
            return $this->fetchFromWayback($keyword);
        });
    }

    private function fetchFromWayback(string $keyword): Collection
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withQueryParameters([
                    'url' => "*{$keyword}*",
                    'matchType' => 'domain',
                    'output' => 'json',
                ])
                ->get(self::API_URL);

            if (!$response->successful()) {
                throw new Exception("Wayback API error: " . $response->status());
            }

            return $this->parseResponse($response->json());
        } catch (Exception $e) {
            throw new Exception("Wayback service error: " . $e->getMessage());
        }
    }

    private function parseResponse(array $data): Collection
    {
        $results = collect();

        if (!isset($data['results']) || empty($data['results'])) {
            return $results;
        }

        foreach ($data['results'] as $result) {
            $results->push([
                'domain' => $result['original'] ?? null,
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
                'archived_url' => $result['archive_url'] ?? null,
                'status_code' => $result['status'] ?? null,
                'timestamp' => $result['timestamp'] ?? null,
            ]);
        }

        return $results;
    }

    public function clearCache(string $keyword): void
    {
        Cache::forget("wayback_search:" . md5($keyword));
    }
}
