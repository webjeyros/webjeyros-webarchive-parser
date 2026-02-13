<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use phpseclib3\Net\WHOIS;
use Exception;

class WhoisService
{
    private const CACHE_TTL = 604800; // 7 days
    private const BATCH_SIZE = 50;

    public function isDomainAvailable(string $domain): bool
    {
        $cacheKey = "whois:available:" . $domain;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            try {
                $whoisData = $this->queryWhois($domain);
                return $this->parseAvailability($whoisData);
            } catch (Exception $e) {
                // If WHOIS fails, assume domain might be available (conservative approach)
                return true;
            }
        });
    }

    public function checkBatch(array $domains): Collection
    {
        $results = collect();

        foreach (array_chunk($domains, self::BATCH_SIZE) as $chunk) {
            foreach ($chunk as $domain) {
                $results->push([
                    'domain' => $domain,
                    'available' => $this->isDomainAvailable($domain),
                ]);
            }
        }

        return $results;
    }

    private function queryWhois(string $domain): string
    {
        try {
            // Using native WHOIS command as fallback
            $command = "whois " . escapeshellarg($domain);
            return shell_exec($command) ?? '';
        } catch (Exception $e) {
            throw new Exception("WHOIS query failed: " . $e->getMessage());
        }
    }

    private function parseAvailability(string $whoisData): bool
    {
        $unavailablePatterns = [
            'No Found',
            'No matching',
            'Not found',
            'object does not exist',
            'not registered',
            'Status: clientDeleteProhibited',
            'Status: clientHold',
            'Status: clientTransferProhibited',
        ];

        foreach ($unavailablePatterns as $pattern) {
            if (stripos($whoisData, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }
}
