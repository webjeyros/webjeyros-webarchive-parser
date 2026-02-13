<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\DomainCheckerService;
use App\Services\SeoMetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Exception;

class CheckDomainSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;
    public $backoff = 60; // Retry after 60 seconds

    public function __construct(
        private Domain $domain,
    ) {}

    public function handle(
        DomainCheckerService $checkerService,
        SeoMetricsService $seoService
    ): void {
        try {
            $this->domain->update(['status' => 'checking']);

            // Check domain availability and basic info
            $availabilityCheck = $checkerService->comprehensiveCheck($this->domain->domain);

            // Get SEO metrics
            $seoMetrics = $seoService->getSeoMetrics($this->domain->domain);

            // Update domain with collected data
            DB::transaction(function () use ($availabilityCheck, $seoMetrics) {
                $updateData = [
                    'available' => $availabilityCheck['available'] ?? false,
                    'http_status_code' => $availabilityCheck['http_status'] ?? null,
                    'metrics_checked_at' => now(),
                    'metrics_available' => true,
                ];

                // Add WHOIS data if available
                if (isset($availabilityCheck['availability_data'])) {
                    $availData = $availabilityCheck['availability_data'];
                    $updateData['registrar'] = $availData['registrar'] ?? null;
                    $updateData['created_date'] = $availData['created_date'] ?? null;
                    $updateData['expiration_date'] = $availData['expiration_date'] ?? null;
                    $updateData['updated_date'] = $availData['updated_date'] ?? null;
                    $updateData['nameserver_1'] = $availData['nameserver_1'] ?? null;
                    $updateData['nameserver_2'] = $availData['nameserver_2'] ?? null;
                    $updateData['nameserver_3'] = $availData['nameserver_3'] ?? null;
                }

                // Add SEO metrics if available
                if (isset($seoMetrics['meta_tags']) && !isset($seoMetrics['meta_tags']['error'])) {
                    $metaTags = $seoMetrics['meta_tags'];
                    // Store as JSON if database supports it, otherwise just in title/snippet
                    // We'll use the title field to store page title
                    if (isset($metaTags['title'])) {
                        $updateData['title'] = $metaTags['title'];
                    }
                }

                // Determine final status
                if ($availabilityCheck['available'] === false && $availabilityCheck['http_status'] > 0) {
                    $updateData['status'] = 'occupied';
                } elseif ($availabilityCheck['available'] === true) {
                    $updateData['status'] = 'available';
                } elseif ($availabilityCheck['http_status'] === 0 || is_null($availabilityCheck['http_status'])) {
                    $updateData['status'] = 'dead';
                } else {
                    $updateData['status'] = 'in_work';
                }

                $this->domain->update($updateData);
            });

            logger()->info("Domain SEO check completed: {$this->domain->domain}", [
                'status' => $this->domain->status,
                'available' => $this->domain->available,
            ]);
        } catch (Exception $e) {
            logger()->error("Domain SEO check failed for {$this->domain->domain}: " . $e->getMessage(), [
                'domain_id' => $this->domain->id,
                'exception' => $e,
            ]);

            $this->domain->update(['status' => 'dead']);
            $this->fail($e);
        }
    }
}
