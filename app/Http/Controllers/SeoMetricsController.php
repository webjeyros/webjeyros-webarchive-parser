<?php

namespace App\Http\Controllers;

use App\Jobs\CheckSeoMetricsJob;
use App\Models\Domain;
use App\Services\SeoQuakeAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoMetricsController extends Controller
{
    public function __construct(
        private SeoQuakeAnalyticsService $analyticsService,
    ) {}

    /**
     * Get SEO metrics for a specific domain
     */
    public function show(Domain $domain): JsonResponse
    {
        try {
            $metric = $domain->metric;

            if (!$metric) {
                return response()->json([
                    'message' => 'No metrics found for this domain',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'domain' => $domain->domain,
                    'metrics' => $metric->toSeoArray(),
                    'available_metrics' => $metric->getAvailableMetrics(),
                    'last_checked' => $metric->seo_metrics_checked_at,
                    'source' => $metric->seo_metrics_source,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check/refresh SEO metrics for a domain (async)
     */
    public function check(Domain $domain): JsonResponse
    {
        try {
            // Dispatch job to check metrics asynchronously
            CheckSeoMetricsJob::dispatch($domain, force: true)
                ->onQueue('default');

            return response()->json([
                'success' => true,
                'message' => 'SEO metrics check has been queued',
                'data' => [
                    'domain' => $domain->domain,
                    'status' => 'queued',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check metrics for multiple domains
     */
    public function checkBatch(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'domain_ids' => 'required|array',
                'domain_ids.*' => 'required|integer|exists:domains,id',
            ]);

            $domains = Domain::whereIn('id', $validated['domain_ids'])->get();

            foreach ($domains as $domain) {
                CheckSeoMetricsJob::dispatch($domain, force: true)
                    ->onQueue('default');
            }

            return response()->json([
                'success' => true,
                'message' => "Queued SEO metrics check for {$domains->count()} domains",
                'data' => [
                    'queued_count' => $domains->count(),
                    'domains' => $domains->pluck('domain'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get metrics for all domains in a project
     */
    public function projectMetrics(int $projectId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 50);
            
            $domains = Domain::where('project_id', $projectId)
                ->with('metric')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'project_id' => $projectId,
                    'total' => $domains->total(),
                    'per_page' => $domains->perPage(),
                    'current_page' => $domains->currentPage(),
                    'last_page' => $domains->lastPage(),
                    'domains' => $domains->map(fn($domain) => [
                        'id' => $domain->id,
                        'domain' => $domain->domain,
                        'status' => $domain->status,
                        'metrics' => $domain->metric?->getAvailableMetrics() ?? [],
                        'last_checked' => $domain->metric?->seo_metrics_checked_at,
                    ]),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Filter domains by SEO metrics
     */
    public function filterByMetrics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
                'min_yandex_index' => 'nullable|integer|min:0',
                'min_yandex_backlinks' => 'nullable|integer|min:0',
                'min_yandex_tic' => 'nullable|integer|min:0',
                'min_google_index' => 'nullable|integer|min:0',
                'min_alexa_rank' => 'nullable|integer|min:0',
                'max_alexa_rank' => 'nullable|integer|min:0',
                'min_webarchive_age' => 'nullable|integer|min:0',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Domain::where('project_id', $validated['project_id'])
                ->with('metric')
                ->whereHas('metric');

            // Apply filters
            if (isset($validated['min_yandex_index'])) {
                $query->whereHas('metric', fn($q) => 
                    $q->where('yandex_index', '>=', $validated['min_yandex_index'])
                );
            }

            if (isset($validated['min_yandex_backlinks'])) {
                $query->whereHas('metric', fn($q) => 
                    $q->where('yandex_backlinks', '>=', $validated['min_yandex_backlinks'])
                );
            }

            if (isset($validated['min_yandex_tic'])) {
                $query->whereHas('metric', fn($q) => 
                    $q->where('yandex_tic', '>=', $validated['min_yandex_tic'])
                );
            }

            if (isset($validated['min_google_index'])) {
                $query->whereHas('metric', fn($q) => 
                    $q->where('google_index', '>=', $validated['min_google_index'])
                );
            }

            if (isset($validated['min_alexa_rank'])) {
                $query->whereHas('metric', fn($q) => 
                    $q->where('alexa_rank', '<=', $validated['min_alexa_rank'])
                );
            }

            if (isset($validated['max_alexa_rank'])) {
                $query->whereHas('metric', fn($q) => 
                    $q->where('alexa_rank', '<=', $validated['max_alexa_rank'])
                );
            }

            if (isset($validated['min_webarchive_age'])) {
                $query->whereHas('metric', fn($q) => 
                    $q->where('webarchive_age', '>=', $validated['min_webarchive_age'])
                );
            }

            $domains = $query->paginate($validated['per_page'] ?? 50);

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $domains->total(),
                    'per_page' => $domains->perPage(),
                    'current_page' => $domains->currentPage(),
                    'domains' => $domains->map(fn($domain) => [
                        'id' => $domain->id,
                        'domain' => $domain->domain,
                        'status' => $domain->status,
                        'metrics' => $domain->metric->toSeoArray(),
                    ]),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export domains with metrics to CSV
     */
    public function exportMetrics(Request $request)
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
            ]);

            $domains = Domain::where('project_id', $validated['project_id'])
                ->with('metric')
                ->get();

            $csv = $this->generateCsv($domains);

            return response()->stream(fn() => print($csv), 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="seo-metrics-' . date('Y-m-d-His') . '.csv"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate CSV content from domains
     */
    private function generateCsv(\Illuminate\Database\Eloquent\Collection $domains): string
    {
        $output = fopen('php://memory', 'r+');
        
        // Header
        $headers = [
            'Domain',
            'Status',
            'Google Index',
            'Google Backlinks',
            'Yandex Index',
            'Yandex Backlinks',
            'Yandex TIC',
            'Yahoo Index',
            'Bing Index',
            'Baidu Index',
            'SEMrush Rank',
            'SEMrush Links',
            'SEMrush Traffic',
            'Alexa Rank',
            'Web Archive Age',
            'Facebook Likes',
            'Last Checked',
        ];
        
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($domains as $domain) {
            $metric = $domain->metric;
            fputcsv($output, [
                $domain->domain,
                $domain->status,
                $metric?->google_index,
                $metric?->google_backlinks,
                $metric?->yandex_index,
                $metric?->yandex_backlinks,
                $metric?->yandex_tic,
                $metric?->yahoo_index,
                $metric?->bing_index,
                $metric?->baidu_index,
                $metric?->semrush_rank,
                $metric?->semrush_links,
                $metric?->semrush_traffic,
                $metric?->alexa_rank,
                $metric?->webarchive_age,
                $metric?->facebook_likes,
                $metric?->seo_metrics_checked_at,
            ]);
        }
        
        rewind($output);
        return stream_get_contents($output);
    }
}
