<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\DomainAvailabilityService;
use App\Services\WhoisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class CheckDomainAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;

    public function __construct(private Domain $domain) {}

    public function handle(
        DomainAvailabilityService $availabilityService,
        WhoisService $whoisService
    ): void {
        try {
            // Check if domain responds to HTTP requests
            $httpCheck = $availabilityService->checkDomain($this->domain->domain);
            
            $this->domain->update([
                'http_status' => $httpCheck['http_status'],
                'status' => $httpCheck['status'] === 'alive' ? 'checking' : 'dead',
                'checked_at' => now(),
            ]);

            // If domain is dead, mark as such and skip WHOIS
            if ($httpCheck['status'] === 'dead') {
                $this->domain->markAsDead();
                return;
            }

            // Check WHOIS availability
            $isAvailable = $whoisService->isDomainAvailable($this->domain->domain);

            if ($isAvailable) {
                $this->domain->markAsAvailable();
                // Dispatch metrics check job
                CheckDomainMetricsJob::dispatch($this->domain)->onQueue('low');
            } else {
                $this->domain->markAsOccupied();
            }
        } catch (Exception $e) {
            logger()->error("Domain availability check failed for {$this->domain->domain}: " . $e->getMessage());
            $this->release(300); // Retry after 5 minutes
        }
    }
}
