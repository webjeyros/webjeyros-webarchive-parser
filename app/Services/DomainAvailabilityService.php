<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class DomainAvailabilityService
{
    private const HTTP_TIMEOUT = 5;
    private const BATCH_SIZE = 50;

    public function checkDomain(string $domain): array
    {
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->get("http://{$domain}", [
                    'User-Agent' => 'Mozilla/5.0 (Web Archive Parser)',
                ])
                ->throw();

            return [
                'domain' => $domain,
                'status' => 'alive',
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'domain' => $domain,
                'status' => 'dead',
                'http_status' => $this->extractStatusCode($e),
            ];
        }
    }

    public function checkBatch(array $domains): array
    {
        $results = [];

        foreach (array_chunk($domains, self::BATCH_SIZE) as $chunk) {
            foreach ($chunk as $domain) {
                $results[] = $this->checkDomain($domain);
            }
        }

        return $results;
    }

    private function extractStatusCode(\Throwable $e): int
    {
        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            return $e->getResponse()->getStatusCode();
        }

        return 0;
    }
}
