<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\DomainMetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class CheckDomainMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = [300, 600, 900]; // Exponential backoff

    public function __construct(private Domain $domain) {}

    public function handle(DomainMetricsService $metricsService): void
    {
        try {
            $metricsService->updateDomainMetrics($this->domain);
        } catch (Exception $e) {
            logger()->error("Domain metrics check failed for {$this->domain->domain}: " . $e->getMessage());
            // Don't fail, as metrics are not critical
            $this->release(600); // Retry after 10 minutes
        }
    }
}
