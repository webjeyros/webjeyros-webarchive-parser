<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\SeoQuakeAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSeoMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Indicates if the job should be marked as failed on any exception.
     */
    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Domain $domain,
        public bool $force = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SeoQuakeAnalyticsService $analyticsService): void
    {
        try {
            Log::info("Starting SEO metrics check for domain: {$this->domain->domain}");

            // Fetch all metrics from SEOquake-like services
            $metrics = $analyticsService->fetchAllMetrics($this->domain->domain);

            if (empty($metrics)) {
                Log::warning("No metrics found for domain: {$this->domain->domain}");
                return;
            }

            // Prepare data for update
            $metricData = [
                'google_index' => $metrics['google_index'] ?? null,
                'google_backlinks' => $metrics['google_links'] ?? null,
                'google_cache_date' => $metrics['google_cache_date'] ?? null,
                'yandex_index' => $metrics['yandex_index'] ?? null,
                'yandex_backlinks' => $metrics['yandex_backlinks'] ?? null,
                'yandex_tic' => $metrics['yandex_tic'] ?? null,
                'yahoo_index' => $metrics['yahoo_index'] ?? null,
                'bing_index' => $metrics['bing_index'] ?? null,
                'baidu_index' => $metrics['baidu_index'] ?? null,
                'baidu_links' => $metrics['baidu_links'] ?? null,
                'semrush_links' => $metrics['semrush_links'] ?? null,
                'semrush_links_domain' => $metrics['semrush_links_domain'] ?? null,
                'semrush_links_host' => $metrics['semrush_links_host'] ?? null,
                'semrush_rank' => $metrics['semrush_rank'] ?? null,
                'semrush_traffic' => $metrics['semrush_traffic'] ?? null,
                'semrush_traffic_price' => $metrics['semrush_traffic_price'] ?? null,
                'alexa_rank' => $metrics['alexa_rank'] ?? null,
                'webarchive_age' => $metrics['webarchive_age'] ?? null,
                'facebook_likes' => $metrics['facebook_likes'] ?? null,
                'compete_rank' => $metrics['compete_rank'] ?? null,
                'seo_metrics_checked_at' => now(),
                'seo_metrics_source' => 'seo_quake_analytics',
            ];

            // Save metrics
            $this->domain->metric()->updateOrCreate(
                ['domain_id' => $this->domain->id],
                $metricData
            );

            Log::info("Successfully updated SEO metrics for domain: {$this->domain->domain}");
        } catch (\Exception $e) {
            Log::error("Error checking SEO metrics for {$this->domain->domain}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $e): void
    {
        Log::error("Job failed for domain {$this->domain->domain}: " . $e->getMessage());
    }
}
