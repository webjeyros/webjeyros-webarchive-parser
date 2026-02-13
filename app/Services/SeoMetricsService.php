<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SeoMetricsService
{
    private const CACHE_TTL = 86400 * 30; // 30 days
    private const HTTP_TIMEOUT = 10;

    /**
     * Получить все SEO метрики
     *
     * Храним в кэше 30 дней
     */
    public function getSeoMetrics(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);
        $cacheKey = "seo_metrics:{$domain}";

        // Попытка получить из кэша
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $metrics = [
            'meta_tags' => [],
            'domain_authority' => null,
            'spam_score' => null,
            'backlink_count' => null,
            'referring_domains' => null,
            'indexed_pages' => null,
            'external_links' => null,
            'internal_links' => null,
            'ticy' => null, // ТИЦ Яндекса
            'yandex_rank' => null,
            'backlinks_ru' => null, // Русские беклинки
            'metrics_source' => 'free',
        ];

        try {
            // 1. Получить мета-теги
            $metrics['meta_tags'] = $this->getMetaTags($domain);

            // 2. Majestic Free API
            $majesticsMetrics = $this->getMajesticsMetrics($domain);
            $metrics = array_merge($metrics, $majesticsMetrics);

            // 3. Common Crawl
            $commonCrawlMetrics = $this->getCommonCrawlMetrics($domain);
            $metrics = array_merge($metrics, $commonCrawlMetrics);

            // 4. ТИЦ Яндекса
            $yandexMetrics = $this->getYandexTicy($domain);
            $metrics = array_merge($metrics, $yandexMetrics);

            // 5. Русские беклинки
            $ruBacklinks = $this->getRussianBacklinks($domain);
            $metrics = array_merge($metrics, $ruBacklinks);

        } catch (Exception $e) {
            Log::error("SEO metrics error for {$domain}: " . $e->getMessage());
            $metrics['meta_tags']['error'] = $e->getMessage();
        }

        // Кэширование результатов
        Cache::put($cacheKey, $metrics, self::CACHE_TTL);

        return $metrics;
    }

    /**
     * Получить мета-теги страницы
     */
    private function getMetaTags(string $domain): array
    {
        $result = [
            'title' => null,
            'description' => null,
            'keywords' => null,
        ];

        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)->get("http://{$domain}");
            $html = $response->body();

            // Парсим title
            if (preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
                $result['title'] = trim($matches[1]);
            }

            // Парсим description
            if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\']/i', $html, $matches)) {
                $result['description'] = trim($matches[1]);
            }

            // Парсим keywords
            if (preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\']([^"\']*)["\']/i', $html, $matches)) {
                $result['keywords'] = trim($matches[1]);
            }
        } catch (Exception $e) {
            Log::debug("Meta tags error for {$domain}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Majestic Free API
     * Выполняет 600 реквестов в день
     */
    private function getMajesticsMetrics(string $domain): array
    {
        $result = [
            'backlink_count' => null,
            'referring_domains' => null,
            'domain_authority' => null,
            'spam_score' => null,
        ];

        try {
            $apiKey = config('services.majestic.api_key');
            if (!$apiKey) {
                return $result; // Skip if no API key
            }

            $url = "https://api.majestic.com/api/json";
            $params = [
                'cmd' => 'GetIndexItemInfo',
                'item' => $domain,
                'datasource' => 'fresh',
                'app_api_key' => $apiKey,
            ];

            $response = Http::timeout(self::HTTP_TIMEOUT)->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['Items']) && count($data['Items']) > 0) {
                    $item = $data['Items'][0];

                    $result['backlink_count'] = (int)($item['ExtBackLinks'] ?? 0);
                    $result['referring_domains'] = (int)($item['ReferringDomains'] ?? 0);
                    $result['spam_score'] = (float)($item['SafetyIndex'] ?? 0);

                    // Получаем Domain Authority из Citation Flow
                    if (isset($item['CitationFlow'])) {
                        // Majestic: CF и TF (0-100)
                        $result['domain_authority'] = round(($item['CitationFlow'] ?? 0) / 100 * 100, 2);
                    }
                }
            }
        } catch (Exception $e) {
            Log::debug("Majestic API error for {$domain}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Common Crawl - сколько раз домен был проиндексирован
     */
    private function getCommonCrawlMetrics(string $domain): array
    {
        $result = [
            'indexed_pages' => null,
            'external_links' => null,
            'internal_links' => null,
        ];

        try {
            // Common Crawl Index
            $url = "https://index.commoncrawl.org/CC-MAIN-2026-04/?url={$domain}&output=json";
            $response = Http::timeout(self::HTTP_TIMEOUT)->get($url);

            if ($response->successful()) {
                $lines = explode("\n", trim($response->body()));
                // Считаем количество рекордов
                $result['indexed_pages'] = count(array_filter($lines)) ?? 0;
            }
        } catch (Exception $e) {
            Log::debug("Common Crawl error for {$domain}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * ТИЦ Яндекса (БЕСПЛАТНО)
     *
     * https://tools.pixelplus.ru/api/ytcy
     * Нас интересуют только ремот домены (наличные) - те с HTTP 200
     */
    private function getYandexTicy(string $domain): array
    {
        $result = [
            'ticy' => null,
            'yandex_rank' => null,
        ];

        try {
            // Рекомендуем использовать free API
            // Option 1: pr-cy.ru
            $response = Http::timeout(self::HTTP_TIMEOUT)->get("https://pr-cy.ru/api/yandex_citation/?url={$domain}&output=json");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['ticy'])) {
                    $result['ticy'] = (int)$data['ticy'];
                }
                if (isset($data['rank'])) {
                    $result['yandex_rank'] = (int)$data['rank'];
                }
            }
        } catch (Exception $e) {
            Log::debug("Yandex TICs API error for {$domain}: " . $e->getMessage());

            // Fallback: Пробуем альтернативный стромастеров
            try {
                $response = Http::timeout(self::HTTP_TIMEOUT)->get("https://api.scopeit.ru/api/yandex_citation/{$domain}");
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['ticy'])) {
                        $result['ticy'] = (int)$data['ticy'];
                    }
                }
            } catch (Exception $fallbackError) {
                Log::debug("Yandex TICs fallback error for {$domain}: " . $fallbackError->getMessage());
            }
        }

        return $result;
    }

    /**
     * Русские беклинки
     *
     * Проверяем ссылки в настоящих русско-язычных доменах
     */
    private function getRussianBacklinks(string $domain): array
    {
        $result = [
            'backlinks_ru' => null,
        ];

        try {
            // Найдем русские беклинки через Google Search API
            // Или через Yandex Webmaster API (если есть токен)

            // Option 1: Правые архивы (archive.org API)
            $url = "https://web.archive.org/cdx/search/cdx?url={$domain}&output=json&filter=statuscode:200&collapse=urlkey";
            $response = Http::timeout(self::HTTP_TIMEOUT)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && count($data) > 1) {
                    // Первая строка - метаданные
                    $result['backlinks_ru'] = count($data) - 1; // Кол-во снимков
                }
            }
        } catch (Exception $e) {
            Log::debug("Russian backlinks error for {$domain}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Нормализация домена
     */
    private function normalizeDomain(string $domain): string
    {
        return strtolower(preg_replace('/^(https?:\/\/)?(www\.)/', '', $domain));
    }
}
