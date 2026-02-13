<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;
use Throwable;

class DomainCheckerService
{
    // Using free WHOIS APIs and DNS checks
    private const WHOISJSON_API = 'https://whoisjson.com/api/v1';
    private const CACHE_TTL = 86400; // 1 day
    private const TIMEOUT = 10;

    /**
     * Check if domain is available for registration
     */
    public function checkAvailability(string $domain): array
    {
        $domain = $this->cleanDomain($domain);
        $cacheKey = "domain_availability:" . $domain;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            return $this->fetchAvailability($domain);
        });
    }

    /**
     * Check HTTP status and basic domain info
     */
    public function checkHttpStatus(string $domain): array
    {
        $domain = $this->cleanDomain($domain);
        $cacheKey = "domain_http_status:" . $domain;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            return $this->fetchHttpStatus($domain);
        });
    }

    /**
     * Get basic DNS and WHOIS info using free methods
     */
    public function getDomainInfo(string $domain): array
    {
        $domain = $this->cleanDomain($domain);
        $cacheKey = "domain_info:" . $domain;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            return $this->fetchDomainInfo($domain);
        });
    }

    /**
     * Check DNS records
     */
    public function checkDNS(string $domain): array
    {
        $domain = $this->cleanDomain($domain);
        $cacheKey = "domain_dns:" . $domain;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            return $this->fetchDNS($domain);
        });
    }

    /**
     * Comprehensive domain check (all methods)
     */
    public function comprehensiveCheck(string $domain): array
    {
        try {
            $domain = $this->cleanDomain($domain);

            $availability = $this->checkAvailability($domain);
            $httpStatus = $this->checkHttpStatus($domain);
            $domainInfo = $this->getDomainInfo($domain);
            $dnsInfo = $this->checkDNS($domain);

            return [
                'domain' => $domain,
                'available' => $availability['available'] ?? false,
                'availability_data' => $availability,
                'http_status' => $httpStatus['status'] ?? null,
                'http_data' => $httpStatus,
                'domain_info' => $domainInfo,
                'dns_info' => $dnsInfo,
                'checked_at' => now(),
            ];
        } catch (Throwable $e) {
            return [
                'domain' => $domain ?? 'unknown',
                'error' => $e->getMessage(),
                'checked_at' => now(),
            ];
        }
    }

    /**
     * Fetch availability data from WhoisJSON API (free)
     */
    private function fetchAvailability(string $domain): array
    {
        try {
            // Using ICANN registry check method
            $response = Http::timeout(self::TIMEOUT)
                ->get("https://api.whoisxmlapi.com/v1/whois-lookup", [
                    'domainName' => $domain,
                    'format' => 'json',
                    'outputFormat' => 'JSON',
                ])
                ->json();

            // Fallback to simple DNS check if API limited
            if (isset($response['statusCode']) && $response['statusCode'] != 0) {
                return $this->checkViaDNS($domain);
            }

            $whoisData = $response['whoisData'] ?? [];

            return [
                'available' => empty($whoisData),
                'registrar' => $whoisData['registrar'] ?? null,
                'created_date' => $whoisData['createdDate'] ?? null,
                'expiration_date' => $whoisData['expiresDate'] ?? null,
                'updated_date' => $whoisData['updatedDate'] ?? null,
                'nameserver_1' => $whoisData['nameServers'][0] ?? null,
                'nameserver_2' => $whoisData['nameServers'][1] ?? null,
                'nameserver_3' => $whoisData['nameServers'][2] ?? null,
                'source' => 'whoisxml',
            ];
        } catch (Throwable $e) {
            logger()->debug("WhoisXML API error: " . $e->getMessage());
            // Fallback to DNS check
            return $this->checkViaDNS($domain);
        }
    }

    /**
     * Check availability via DNS (completely free)
     */
    private function checkViaDNS(string $domain): array
    {
        try {
            $records = @dns_get_record($domain, DNS_ANY);
            $available = empty($records);

            $nameservers = [];
            if (!empty($records)) {
                foreach ($records as $record) {
                    if ($record['type'] === 'NS') {
                        $nameservers[] = $record['target'];
                    }
                }
            }

            return [
                'available' => $available,
                'has_dns_records' => !$available,
                'nameservers' => $nameservers,
                'source' => 'dns_check',
            ];
        } catch (Throwable $e) {
            logger()->debug("DNS check error: " . $e->getMessage());
            return [
                'available' => null,
                'error' => 'dns_check_failed',
                'source' => 'dns_check',
            ];
        }
    }

    /**
     * Fetch HTTP status
     */
    private function fetchHttpStatus(string $domain): array
    {
        try {
            $url = "https://" . $domain;

            // Try HTTPS first
            try {
                $response = Http::timeout(5)
                    ->withoutVerifying() // Skip SSL for dead domains
                    ->head($url);

                return [
                    'status' => $response->status(),
                    'available' => in_array($response->status(), [200, 301, 302, 303, 304, 307, 308]),
                    'protocol' => 'https',
                    'checked_at' => now(),
                ];
            } catch (Throwable $e) {
                // Try HTTP
                $url = "http://" . $domain;
                $response = Http::timeout(5)->head($url);

                return [
                    'status' => $response->status(),
                    'available' => in_array($response->status(), [200, 301, 302, 303, 304, 307, 308]),
                    'protocol' => 'http',
                    'checked_at' => now(),
                ];
            }
        } catch (Throwable $e) {
            return [
                'status' => 0,
                'available' => false,
                'error' => $e->getMessage(),
                'checked_at' => now(),
            ];
        }
    }

    /**
     * Get domain info
     */
    private function fetchDomainInfo(string $domain): array
    {
        return [
            'domain' => $domain,
            'tld' => $this->extractTLD($domain),
            'checked_at' => now(),
        ];
    }

    /**
     * Fetch DNS records
     */
    private function fetchDNS(string $domain): array
    {
        try {
            $records = [
                'A' => dns_get_record($domain, DNS_A),
                'MX' => dns_get_record($domain, DNS_MX),
                'NS' => dns_get_record($domain, DNS_NS),
                'TXT' => dns_get_record($domain, DNS_TXT),
            ];

            // Filter out empty results
            $records = array_filter($records, fn($v) => !empty($v));

            return [
                'records' => $records,
                'has_dns' => !empty($records),
            ];
        } catch (Throwable $e) {
            logger()->debug("DNS records fetch error: " . $e->getMessage());
            return [
                'records' => [],
                'has_dns' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean domain name
     */
    private function cleanDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^https?:\/\//i', '', $domain);
        $domain = preg_replace('/^www\./i', '', $domain);
        $domain = trim($domain, '/\\');

        return $domain;
    }

    /**
     * Extract TLD from domain
     */
    private function extractTLD(string $domain): string
    {
        $parts = explode('.', $domain);
        return end($parts);
    }

    /**
     * Clear cache for domain
     */
    public function clearCache(string $domain): void
    {
        $domain = $this->cleanDomain($domain);
        Cache::forget("domain_availability:" . $domain);
        Cache::forget("domain_http_status:" . $domain);
        Cache::forget("domain_info:" . $domain);
        Cache::forget("domain_dns:" . $domain);
    }
}
