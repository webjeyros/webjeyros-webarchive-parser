<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Exception;

class WaybackAnchorService
{
    private const ANCHOR_API_URL = 'https://web.archive.org/__wb/search/anchor';
    private const CACHE_TTL = 86400; // 1 day
    private const TIMEOUT = 30;

    public function searchByKeyword(string $keyword): Collection
    {
        $cacheKey = "wayback_anchor_search:" . md5($keyword);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($keyword) {
            return $this->fetchFromWayback($keyword);
        });
    }

    private function fetchFromWayback(string $keyword): Collection
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withQueryParameters([
                    'q' => $keyword,
                ])
                ->get(self::ANCHOR_API_URL);

            if (!$response->successful()) {
                throw new Exception("Wayback Anchor API error: " . $response->status());
            }

            return $this->parseResponse($response->json());
        } catch (Exception $e) {
            throw new Exception("Wayback anchor service error: " . $e->getMessage());
        }
    }

    private function parseResponse(array $data): Collection
    {
        $results = collect();

        if (!is_array($data) || empty($data)) {
            return $results;
        }

        foreach ($data as $result) {
            // Check if keyword is present in title (name field) or text field
            $text = strtolower($result['text'] ?? '');
            $name = strtolower($result['name'] ?? '');
            $keyword = strtolower($result['snippet'] ?? '');

            // Only include if keyword appears in text or name
            if (!empty($text) || !empty($name)) {
                $results->push([
                    'domain' => $result['name'] ?? $result['display_name'] ?? null,
                    'title' => $result['text'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                    'display_name' => $result['display_name'] ?? null,
                    'link' => $result['link'] ?? null,
                    'first_captured' => $result['first_captured'] ?? null,
                    'last_captured' => $result['last_captured'] ?? null,
                    'capture_count' => $result['capture'] ?? 0,
                    'webpage_count' => $result['webpage'] ?? 0,
                    'image_count' => $result['image'] ?? 0,
                    'video_count' => $result['video'] ?? 0,
                    'audio_count' => $result['audio'] ?? 0,
                ]);
            }
        }

        return $results;
    }

    public function clearCache(string $keyword): void
    {
        Cache::forget("wayback_anchor_search:" . md5($keyword));
    }
}
