<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\SeoMetricsService;
use App\Services\WhoisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainCheckController extends Controller
{
    private WhoisService $whoisService;
    private SeoMetricsService $seoMetricsService;

    public function __construct(WhoisService $whoisService, SeoMetricsService $seoMetricsService)
    {
        $this->whoisService = $whoisService;
        $this->seoMetricsService = $seoMetricsService;
    }

    /**
     * Проверить домен с фильтром по HTTP 200
     *
     * Проверяем только живые домены (HTTP 200)
     * Для мертвых доменов - пропускаем дорогие запросы к API
     */
    public function checkDomain(Request $request)
    {
        $domain = $request->input('domain');
        $domain = $this->normalizeDomain($domain);

        $domainModel = Domain::firstOrCreate(
            ['domain' => $domain],
            ['available' => true]
        );

        try {
            // 1. Проверяем HTTP статус
            $httpStatus = $this->checkHttpStatus($domain);
            $domainModel->http_status_code = $httpStatus['code'];
            $domainModel->last_http_check = now();
            $domainModel->save();

            // 2. Если домен недоступен (не 200) - на этом останавливаемся
            if ($httpStatus['code'] !== 200) {
                Log::info("Домен {$domain} вернул HTTP {$httpStatus['code']} - пропускаем SEO метрики");
                return response()->json([
                    'domain' => $domain,
                    'status' => 'dead',
                    'http_code' => $httpStatus['code'],
                    'message' => 'Домен недоступен - дополнительных проверок не требуется',
                ], 200);
            }

            // 3. Проверяем WHOIS (только для живых доменов)
            $whoisData = $this->whoisService->getWhoisData($domain);
            if ($whoisData) {
                $domainModel->fill($whoisData)->save();
            }

            // 4. Получаем SEO метрики (только для живых доменов)
            $seoMetrics = $this->seoMetricsService->getSeoMetrics($domain);

            // Сохраняем SEO метрики
            $domainModel->update([
                'backlink_count' => $seoMetrics['backlink_count'] ?? null,
                'referring_domains' => $seoMetrics['referring_domains'] ?? null,
                'domain_authority' => $seoMetrics['domain_authority'] ?? null,
                'spam_score' => $seoMetrics['spam_score'] ?? null,
                'indexed_pages' => $seoMetrics['indexed_pages'] ?? null,
                'external_links' => $seoMetrics['external_links'] ?? null,
                'internal_links' => $seoMetrics['internal_links'] ?? null,
                // NEW: Yandex metrics
                'ticy' => $seoMetrics['ticy'] ?? null,
                'yandex_rank' => $seoMetrics['yandex_rank'] ?? null,
                'backlinks_ru' => $seoMetrics['backlinks_ru'] ?? null,
                'metrics_source' => $seoMetrics['metrics_source'] ?? 'free',
                'metrics_checked_at' => now(),
                'metrics_available' => true,
                'available' => true,
            ]);

            return response()->json([
                'domain' => $domain,
                'status' => 'active',
                'http_code' => $httpStatus['code'],
                'seo_metrics' => $seoMetrics,
                'whois_data' => $whoisData,
                'message' => 'Полная проверка завершена успешно',
            ], 200);

        } catch (\Exception $e) {
            Log::error("Ошибка при проверке домена {$domain}: " . $e->getMessage());
            return response()->json([
                'domain' => $domain,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Проверить HTTP статус домена
     *
     * Возвращает код ответа (200, 404, 503 и т.д.)
     * Ключевый момент: проверяем ТОЛЬКО Response Code, не сохраняя данные
     */
    private function checkHttpStatus(string $domain): array
    {
        try {
            // Пробуем http сначала
            try {
                $response = Http::timeout(5)->get("http://{$domain}");
                return [
                    'code' => $response->status(),
                    'live' => $response->status() === 200,
                    'protocol' => 'http',
                ];
            } catch (\Exception $e) {
                // Если http не работает, пробуем https
                $response = Http::timeout(5)->get("https://{$domain}");
                return [
                    'code' => $response->status(),
                    'live' => $response->status() === 200,
                    'protocol' => 'https',
                ];
            }
        } catch (\Exception $e) {
            Log::debug("HTTP check error for {$domain}: " . $e->getMessage());
            return [
                'code' => 0, // Connection error
                'live' => false,
                'protocol' => 'unknown',
            ];
        }
    }

    /**
     * Пакетная проверка доменов
     *
     * Проверяет только живые домены (HTTP 200)
     */
    public function batchCheck(Request $request)
    {
        $domains = $request->input('domains', []);
        $results = [];
        $errors = [];

        foreach ($domains as $domain) {
            try {
                $domain = $this->normalizeDomain($domain);

                // Быстрая проверка HTTP статуса
                $httpStatus = $this->checkHttpStatus($domain);

                if ($httpStatus['code'] === 200) {
                    // Только живые домены
                    $seoMetrics = $this->seoMetricsService->getSeoMetrics($domain);
                    $results[] = [
                        'domain' => $domain,
                        'status' => 'active',
                        'http_code' => 200,
                        'seo_metrics' => $seoMetrics,
                    ];
                } else {
                    // Мертвые домены - минимальная информация
                    $results[] = [
                        'domain' => $domain,
                        'status' => 'dead',
                        'http_code' => $httpStatus['code'],
                        'message' => 'Домен недоступен',
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'domain' => $domain ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'total' => count($domains),
            'active' => count(array_filter($results, fn($r) => $r['status'] === 'active')),
            'dead' => count(array_filter($results, fn($r) => $r['status'] === 'dead')),
            'errors' => count($errors),
            'results' => $results,
            'error_details' => $errors,
        ]);
    }

    /**
     * Получить все домены с фильтром
     */
    public function listDomains(Request $request)
    {
        $query = Domain::query();

        // Фильтр по статусу (live/dead)
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'live') {
                $query->where('http_status_code', 200);
            } elseif ($status === 'dead') {
                $query->where('http_status_code', '!=', 200)->orWhereNull('http_status_code');
            }
        }

        // Фильтр по наличию метрик
        if ($request->input('has_metrics')) {
            $query->where('metrics_available', true);
        }

        // Фильтр по TICs
        if ($request->has('min_ticy')) {
            $query->where('ticy', '>=', (int)$request->input('min_ticy'));
        }

        // Фильтр по бэклинкам
        if ($request->has('min_backlinks')) {
            $query->where('backlink_count', '>=', (int)$request->input('min_backlinks'));
        }

        $domains = $query->paginate($request->input('per_page', 50));

        return response()->json($domains);
    }

    /**
     * Нормализация домена
     */
    private function normalizeDomain(string $domain): string
    {
        return strtolower(preg_replace('/^(https?:\/\/)?(www\.)/', '', $domain));
    }
}
